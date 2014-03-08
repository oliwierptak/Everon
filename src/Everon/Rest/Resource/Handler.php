<?php
/**
 * This file is part of the Everon framework.
 *
 * (c) Oliwier Ptak <oliwierptak@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Everon\Rest\Resource;

use Everon\DataMapper\Criteria;
use Everon\Dependency;
use Everon\Domain\Interfaces\Entity;
use Everon\Rest\Exception;
use Everon\Helper;
use Everon\Http;
use Everon\Rest\Interfaces;

class Handler implements Interfaces\ResourceHandler
{
    use Dependency\Injection\Factory;
    use Dependency\Injection\DomainManager;
    use Dependency\Injection\Request; //todo meh
    use Helper\AlphaId;
    use Helper\Arrays;
    use Helper\Asserts\IsInArray;
    use Helper\Asserts\IsNull;
    use Helper\Exceptions;

    const VERSIONING_URL = 'url';
    const VERSIONING_HEADER = 'header';
    const ALPHA_ID_SALT = 'Vhg656';


    /**
     * @var array
     */
    protected $supported_versions = null;

    /**
     * @var string Versioning type. Accepted values are: 'url' or 'header'
     */
    protected $versioning = null;
    
    protected $current_version = null;  //v1, v2, v3...
    
    protected $url = null;  //http://api.localhost:80/

    /**
     * @var \Everon\Interfaces\Collection
     */
    protected $MappingCollection = null;
    

    /**
     * @param $url
     * @param $supported_versions
     * @param $versioning
     */
    public function __construct($url, array $supported_versions, $versioning, array $mapping)
    {
        $this->url = $url;
        $this->supported_versions = $supported_versions;
        $this->current_version = $this->supported_versions[count($supported_versions)-1];
        $this->versioning = $versioning;
        $this->MappingCollection = new Helper\Collection($mapping);
    }
    
    protected function buildResourceFromEntity(Entity $Entity, $resource_name, $version)
    {
        $this->assertIsInArray($version, $this->supported_versions, 'Unsupported version: "%s"', 'Domain');
        
        $domain_name = $this->getDomainNameFromMapping($resource_name);
        $resource_id = $this->generateResourceId($Entity->getId(), $resource_name);
        $link = $this->getResourceUrl($resource_name, $resource_id);

        $Resource = $this->getFactory()->buildRestResource($domain_name, $version, $link, $Entity); //todo: change version to href
        $this->buildResourceRelations($Resource);

        return $Resource;
    }

    /**
     * @inheritdoc
     */
    public function getResource($resource_id, $resource_name, $version, Interfaces\ResourceNavigator $Navigator)
    {
        try {
            $domain_name = $this->getDomainNameFromMapping($resource_name);
            $id = $this->generateEntityId($resource_id, $domain_name);
            $Repository = $this->getDomainManager()->getRepository($domain_name);
            $Entity = $Repository->getEntityById($id);
            $this->assertIsNull($Entity, sprintf('Domain Entity: "%s" not found', $domain_name), 'Domain');
            $Resource =  $this->buildResourceFromEntity($Entity, $resource_name, $version);
            $link = $this->getResourceUrl($resource_name, $resource_id, '', $this->getRequest()->getQueryString(), $version);
            $Resource->setHref($link);

            $resources_to_expand = $Navigator->getExpand();
            foreach ($resources_to_expand as $collection_name) {
                $domain_name = $this->getDomainNameFromMapping($collection_name);
                if ($domain_name !== null) {
                    /**
                     * @var \Everon\Interfaces\Collection $RelationCollection
                     */
                    $RelationCollection = $Resource->getDomainEntity()->getRelationCollection()[$domain_name];
                    $relation_list = $RelationCollection->toArray();

                    $RelationCollection = new Helper\Collection([]);
                    for ($a=0 ;$a<count($relation_list); $a++) {
                        $CollectionEntity = $relation_list[$a];
                        $RelationCollection->set($a, $this->buildResourceFromEntity($CollectionEntity, $collection_name, $version));
                    }

                    $link = $this->getResourceUrl($resource_name, $resource_id, $collection_name);
                    $CollectionResource = $this->getFactory()->buildRestCollectionResource($domain_name, $version, $link, $RelationCollection);
                    $CollectionResource->setLimit($this->getRequest()->getGetParameter('limit', 10));
                    $CollectionResource->setOffset($this->getRequest()->getGetParameter('offset', 0));

                    $Resource->setRelationCollectionByName($collection_name, $CollectionResource);
                }
            }
            
            return $Resource;
        }
        catch (\Exception $e) {
            throw new Http\Exception\NotFound('Resource: "%s" not found', [$this->getResourceUrl($resource_name, $resource_id)], $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function getCollectionResource($resource_name, $version)
    {
        try {
            $domain_name = $this->getDomainNameFromMapping($resource_name);
            $link = $this->getResourceUrl($resource_name);
            $Repository = $this->getDomainManager()->getRepository($domain_name);
            $Criteria = new Criteria();
            $Criteria->limit($this->getRequest()->getGetParameter('limit', 10));
            $Criteria->offset($this->getRequest()->getGetParameter('offset', 0));
            
            $entity_list = $Repository->getList($Criteria);
    
            $ResourceList = new Helper\Collection([]);
            for ($a=0; $a<count($entity_list); $a++) {
                $CollectionEntity = $entity_list[$a];
                $ResourceList->set($a, $this->buildResourceFromEntity($CollectionEntity, $resource_name, $version));
            }
            
            $CollectionResource = $this->getFactory()->buildRestCollectionResource($domain_name, $version, $link, $ResourceList); //todo: change version to href
            $CollectionResource->setLimit($this->getRequest()->getGetParameter('limit', 10));
            $CollectionResource->setOffset($this->getRequest()->getGetParameter('offset', 0));
            return $CollectionResource;
        }
        catch (\Exception $e) {
            throw new Http\Exception\NotFound('CollectionResource: "%s" not found', [$this->getResourceUrl($resource_name)], $e);
        }
    }

    public function buildResourceRelations(Interfaces\Resource $Resource)
    {
        /**
         * @var \Everon\Domain\Interfaces\Zone\Entity $Entity
         */
        //$Entity = $Resource->getDomainEntity();
        $RelationCollection = new Helper\Collection([]);
        foreach ($Resource->getRelationDefinition() as $resource_name => $resource_domain_name) {
            $link = $this->getResourceUrl($resource_name);
            $RelationCollection->set($resource_name, ['href' => $link]);
        }

        $Resource->setRelationCollection($RelationCollection);
    }

    /**
     * @inheritdoc
     */
    public function generateEntityId($resource_id, $name)
    {
        return $resource_id;
        $name .= static::ALPHA_ID_SALT;
        return $this->alphaId($resource_id, true, 7, $name);
    }

    /**
     * @inheritdoc
     */
    public function generateResourceId($entity_id, $name)
    {
        return $entity_id;
        $name .= static::ALPHA_ID_SALT;
        return $this->alphaId($entity_id, false, 7, $name);
    }

    /**
     * @inheritdoc
     */
    public function getResourceUrl($resource_name, $resource_id=null, $collection=null, $request_path=null, $version=null)
    {
        $version = $version ?: $this->current_version;
        $Href = new Href($this->url, $version, $this->versioning);
        $link = $Href->getLink($resource_name, $resource_id, $collection, $request_path);
        return $link;
    }
    
    /**
     * @inheritdoc
     */
    public function getDomainNameFromMapping($resource_name)
    {
        $domain_name = $this->MappingCollection->get($resource_name, null);
        if ($domain_name === null) {
            throw new Exception\Manager('Invalid rest mapping domain: "%s"', $resource_name);
        }
        
        return $domain_name;
    }

    /**
     * @inheritdoc
     */
    public function getResourceNameFromMapping($domain_name)
    {
        $resource_name = $this->MappingCollection->get($domain_name, null);
        if ($resource_name === null) {
            throw new Exception\Manager('Invalid rest mapping resource: "%s"', $domain_name);
        }

        return $resource_name;
    }
}