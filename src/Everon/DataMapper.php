<?php
/**
 * This file is part of the Everon framework.
 *
 * (c) Oliwier Ptak <oliwierptak@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Everon;

use Everon\DataMapper\Interfaces\Schema;
use Everon\DataMapper\Interfaces\Criteria;
use Everon\DataMapper\Interfaces\Schema\Table;
use Everon\DataMapper\Dependency;
use Everon\Interfaces;

abstract class DataMapper implements Interfaces\DataMapper
{
    use Dependency\Schema;
    
    use Helper\String\LastTokenToName;
    

    /**
     * @var Table
     */
    protected $Table = null;
    
    protected $name = null;
    
    protected $write_connection_name = 'write';
    protected $read_connection_name = 'read';

    /**
     * @inheritdoc
     */
    abstract public function getInsertSql(array $data);
    
    /**
     * @inheritdoc
     */
    abstract public function getUpdateSql(array $data);

    /**
     * @inheritdoc
     */
    abstract public function getDeleteSql($id);

    /**
     * @inheritdoc
     */
    abstract public function getDeleteByCriteriaSql(Criteria $Criteria);

    /**
     * @inheritdoc
     */
    abstract public function getFetchAllSql(Criteria $Criteria=null);

    /**
     * @inheritdoc
     */
    abstract public function getJoinSql($select, $a, $b, $on_a, $on_b, Criteria $Criteria=null, $type='');

    /**
     * @inheritdoc
     */
    abstract public function getCountSql(Criteria $Criteria=null);
    

    /**
     * @param Table $Table
     * @param Schema $Schema
     */
    public function __construct(Table $Table, Schema $Schema)
    {
        $this->Table = $Table;
        $this->Schema = $Schema;
    }

    /**
     * @param string $placeholder
     * @return array
     */
    protected function getPlaceholderForQuery($placeholder=':')
    {
        $placeholders = [];
        $columns = $this->getTable()->getColumns();
        /**
         * @var DataMapper\Interfaces\Schema\Column $Column
         */
        foreach ($columns as $name => $Column) {
            if ($Column->isPk()) {
                continue;
            }
            $placeholders[] = $placeholder.$name;
        }

        return $placeholders;
    }

    /**
     * @param array $data should be in format required by db, all DateTime objects and alike should be gone
     * @param string $delimiter
     * @return array
     */
    protected function getValuesForQuery(array $data, $delimiter='')
    {
        $values = [];
        $columns = $this->getTable()->getColumns();
        /**
         * @var DataMapper\Interfaces\Schema\Column $Column
         */
        foreach ($columns as $name => $Column) {
            if ($Column->isPk()) {
                continue;
            }
            
            $values[$delimiter.$name] = $data[$name];
        }

        return $values;
    }

    /**
     * @inheritdoc
     */
    public function add(array $data)
    {
        list($sql, $parameters) = $this->getInsertSql($data);
        $id = $this->getSchema()->getPdoAdapterByName($this->write_connection_name)->insert($sql, $parameters);
        $id = $this->getTable()->validateId($id);
        $data[$this->getTable()->getPk()] = $id;
        return $data;
    }

    /**
     * @inheritdoc
     */
    public function save(array $data)
    {
        list($sql, $parameters) = $this->getUpdateSql($data);
        return $this->getSchema()->getPdoAdapterByName($this->write_connection_name)->update($sql, $parameters);
    }

    /**
     * @inheritdoc
     */
    public function delete($id)
    {
        $id = $this->getTable()->validateId($id);
        list($sql, $parameters) = $this->getDeleteSql($id);
        return $this->getSchema()->getPdoAdapterByName($this->write_connection_name)->delete($sql, $parameters);
    }

    /**
     * @inheritdoc
     */
    /**
     * @param Criteria $Criteria
     * @return int
     */
    public function deleteByCriteria(Criteria $Criteria)
    {
        list($sql, $parameters) = $this->getDeleteByCriteriaSql($Criteria);
        return $this->getSchema()->getPdoAdapterByName($this->write_connection_name)->delete($sql, $parameters);
    }

    /**
     * @inheritdoc
     */
    public function count(Criteria $Criteria=null)
    {
        if ($Criteria === null) {
            $Criteria = new DataMapper\CriteriaOLD();
        }
        else {
            $Criteria = clone $Criteria;
            $Criteria->orderBy([]);
            $Criteria->offset(0);
            $Criteria->limit(0);
        }
        
        list($sql, $parameters) = $this->getCountSql($Criteria);
        $PdoStatement = $this->getSchema()->getPdoAdapterByName($this->read_connection_name)->execute($sql, $parameters);
        return (int) $PdoStatement->fetchColumn();
    }

    /**
     * @inheritdoc
     */
    public function fetchOneById($id)
    {
        $Criteria = new DataMapper\CriteriaOLD();
        $id = $this->getTable()->validateId($id);
        $Criteria->where([$this->getTable()->getPk() => $id]);
        return $this->fetchOneByCriteria($Criteria);
    }

    /**
     * @inheritdoc
     */
    public function fetchOneByCriteria(Criteria $Criteria)
    {
        $Criteria->limit(1);
        $sql = $this->getFetchAllSql($Criteria);
        return $this->getSchema()->getPdoAdapterByName($this->read_connection_name)->execute($sql, $Criteria->getWhere())->fetch();
    }

    /**
     * @inheritdoc
     */
    public function fetchAll(Criteria $Criteria)
    {
        $sql = $this->getFetchAllSql($Criteria);
        return $this->getSchema()->getPdoAdapterByName($this->read_connection_name)->execute($sql, $Criteria->getWhere())->fetchAll();
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        if ($this->name === null) {
            $this->name = $this->stringLastTokenToName(get_called_class());
        }
        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public function getTable()
    {
        return $this->Table;
    }

    /**
     * @inheritdoc
     */
    public function setTable(Table $Table)
    {
        $this->Table = $Table;
    }

    /**
     * @inheritdoc
     */
    public function setReadConnectionName($read_connection_name)
    {
        $this->read_connection_name = $read_connection_name;
    }

    /**
     * @inheritdoc
     */
    public function getReadConnectionName()
    {
        return $this->read_connection_name;
    }

    /**
     * @inheritdoc
     */
    public function setWriteConnectionName($write_connection_name)
    {
        $this->write_connection_name = $write_connection_name;
    }

    /**
     * @inheritdoc
     */
    public function getWriteConnectionName()
    {
        return $this->write_connection_name;
    }

    /**
     * @inheritdoc
     */
    public function beginTransaction()
    {
        $this->getSchema()->getPdoAdapterByName($this->write_connection_name)->beginTransaction();
    }

    /**
     * @inheritdoc
     */
    public function commitTransaction()
    {
        $this->getSchema()->getPdoAdapterByName($this->write_connection_name)->commitTransaction();
    }

    /**
     * @inheritdoc
     */
    public function rollbackTransaction()
    {
        $this->getSchema()->getPdoAdapterByName($this->write_connection_name)->rollbackTransaction();
    }

}