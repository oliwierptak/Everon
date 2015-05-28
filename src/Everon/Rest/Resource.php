<?php
/**
 * This file is part of the Everon framework.
 *
 * (c) Oliwier Ptak <oliwierptak@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Everon\Rest;

use Everon\Dependency;
use Everon\Helper;
use Everon\Domain\Interfaces\Entity;
use Everon\Interfaces\Collection;


abstract class Resource extends Resource\Basic implements Interfaces\Resource
{
    use Dependency\Injection\Factory;
    
    use Helper\DateFormatter;
    use Helper\String\LastTokenToName;
    
    /**
     * @var Entity
     */
    protected $DomainEntity = null;

    /**
     * Hold MANY type relations
     * 
     * @var Collection
     */
    protected $RelationCollection = null;

    /**
     * Holds ONE type relations
     * 
     * @var Collection
     */
    protected $ResourceCollection = null;
    

    /**
     * @param Interfaces\ResourceHref $Href
     * @param Entity $Entity
     */
    public function __construct(Interfaces\ResourceHref $Href, Entity $Entity)
    {
        parent::__construct($Href);
        $this->DomainEntity = $Entity;
        $this->RelationCollection = new Helper\Collection([]);
        $this->ResourceCollection = new Helper\Collection([]);
    }

    /**
     * @inheritdoc
     */
    public function getDomainEntity()
    {
        return $this->DomainEntity;
    }
    
    public function getDomainName()
    {
        return $this->getDomainEntity()->getDomainName();
    }

    /**
     * @inheritdoc
     */
    public function setRelationCollection(Collection $RelationCollection)
    {
        $this->RelationCollection = $RelationCollection;
    }
    
    /**
     * @inheritdoc
     */
    public function getRelationCollection()
    {
        return $this->RelationCollection;
    }

    /**
     * @inheritdoc
     */
    public function setRelationCollectionByName($name, Interfaces\ResourceCollection $CollectionResource)
    {
        $this->RelationCollection->set($name, $CollectionResource);
    }

    /**
     * @inheritdoc
     */
    public function getRelationCollectionByName($name)
    {
        return $this->RelationCollection->get($name);
    }

    /**
     * @inheritdoc
     */
    public function setResourceCollection(Collection $ResourceCollection)
    {
        $this->ResourceCollection = $ResourceCollection;
    }

    /**
     * @inheritdoc
     */
    public function getResourceCollection()
    {
        return $this->ResourceCollection;
    }

    /**
     * @inheritdoc
     */
    public function setResourceByName($name, array $resource_data)
    {
        $this->ResourceCollection->set($name, $resource_data);
    }

    /**
     * @inheritdoc
     */
    public function getResourceByName($name)
    {
        return $this->ResourceCollection->get($name);
    }

    /**
     * @inheritdoc
     */
    protected function getToArray() //todo: make better interface then toArray() in order to display it as rest data
    {
        $entity_data = $this->DomainEntity->toArray();
       
        //make \DateTime useful, must be in timezone_type 3
        array_walk_recursive($entity_data, function(&$value) {
            if ($value instanceof \DateTime) {
                $value->setTimezone($this->getFactory()->buildDateTime(null)->getTimezone()); //convert to server timezone
                $value = $value->format(\DateTime::ISO8601);
            }
        });

        $entity_data = array_merge($entity_data, $this->ResourceCollection->toArray(true));

        $data = parent::getToArray();
        $data = array_merge($data, $entity_data);
        $data = array_merge($data, $this->RelationCollection->toArray(true));
        return $data;
    }
}
