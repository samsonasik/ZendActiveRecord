<?php
namespace ActiveRecord;

use Zend\Db\TableGateway\Feature\RowGatewayFeature;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\RowGateway\RowGatewayInterface;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;

abstract class Model implements RowGatewayInterface
{
    protected $adapter;
    const DATE_FORMAT = 'Y-m-d H:i:s';
    const LONG_DATE_FORMAT = 'Y-m-d H:i:s.B';

    public function __construct(\Zend\Db\Adapter\Adapter $adapter)
    {
        // explicitly set the platform driver
        $adapter->getPlatform()->setDriver($adapter->getDriver());
        $this->adapter = $adapter;
    }

    /**
     * Creates a new empty row (but does not save it)
     *
     * @return ActiveRecordAbstract
     */
    public function createRow() {
        $row = clone($this);
        $row->reset();
        return $row;
    }

    /**
     * Saves the current record
     *
     */
    public function save() {
        $data = $this->toArray();
        $id = $data['id'];
        unset($data['id']);
        if($id > 0) {
            $this->getTable()->update($data, 'id = ' . $id);
        } else {
            $this->getTable()->insert($data);
            $this->id = $this->getAdapter()->getDriver()->getLastGeneratedValue();
        }
    }

    /**
     * Deletes an existing record
     *
     * If you pass this an id it will delete that record. Otherwise it deletes the current record
     *
     * @param int $id
     */
    public function delete($id = 0) {
        if($id == 0 && $this->id > 0) {
            $id = $this->id;
        }

        $this->getTable()->delete('id = ' . $id);
    }

    /**
     * Runs a query and fetches the complete result set
     *
     * @param mixed array|Select|null $select
     * @return null|\Zend\Db\ResultSet\ResultSetInterface
     */
    public function fetchAll($select = null) {
        // we need this workaround to avoid data issues with RowData interface
        // it uses the current instance as a template, so it shares data from the current instance if its not set in the result rows
        $class = get_called_class();
        $model = new $class($this->getAdapter());
        $table = $model->getTable();
        if($select instanceof Select) {
            $result = $table->selectWith($select);
        } else {
            $result = $table->select($select);
        }

        if($result) {
            foreach($result as $row) {
                $resultArray[] = $row;
            }
        }
        return isset($resultArray) ? $resultArray : null;
    }

    /**
     * Runs a query and fetches a single row
     *
     * @param mixed array|Select|null $select
     * @return null|ActiveRecordAbstract
     */
    public function fetchRow($select = null) {
        $result = $this->fetchAll($select);
        if(count($result) > 0) {
            return $result[0];
        }
    }

    /**
     * Finds a record by the id
     *
     * @param $id
     * @return null|ActiveRecordAbstract
     */
    public function find($id) {
        $select = $this->select();
        $select->where(array('id' => $id));
        return $this->fetchRow($select);
    }

    /**
     * Counts the matching records in the database
     *
     * @param null $select
     * @return int
     */
    public function count($select = null) {
        if(null === $select) $select = $this->select();
        $adapter = $this->getAdapter();
        $sql = new Sql($adapter);
        $select->columns(array('num' => new \Zend\Db\Sql\Expression('COUNT(*)')));

        $selectString = $sql->getSqlStringForSqlObject($select);
        $results = $adapter->query($selectString, $adapter::QUERY_MODE_EXECUTE);
        return $results->current()->num;
    }

    /**
     * Loads an array into the current record
     *
     * @param array $data
     */
    public function exchangeArray($data) {
        foreach(array_keys($this->toArray()) as $prop) {
            if(isset($data[$prop])) {
                $this->$prop = $data[$prop];
            }
        }
    }

    /**
     * Formats the current time for the db
     *
     * @return bool|string
     */
    public function now($microtime = false) {
        $format = $microtime ? self::DATE_FORMAT : self::LONG_DATE_FORMAT;
        $now = date($format);
        return $now;
    }



    /**
     * returns the table adapter instance
    */
    public function getTable() {
        $table = new TableGateway($this->tableName, $this->adapter, new RowGatewayFeature($this));
        return $table;
    }

    public function getTableName() {
        return $this->tableName;
    }

    /**
     * Returns the Zend Select object for the current table
     *
     * @link http://framework.zend.com/manual/2.2/en/modules/zend.db.sql.html#zend-db-sql-select
     * @return Select
     */
    public function select() {
        return $this->getTable()->getSql()->select();
    }

    /**
     * Fetches the current db adapter instance
     *
     * @return mixed
     */
    public function getAdapter() {
        return $this->adapter;
    }

    /**
     * Alias for toArray()
     *
     * @return mixed
     */
    public function getArrayCopy() {
        return $this->toArray();
    }

    /**
     * Converts all of the records properties to an array
     *
     * @return mixed
     */
    public function toArray() {
        foreach($this->getProperties() as $prop) {
            $key = $prop->name;
            $publicProperties[$key] = $this->$key;
        }
        return $publicProperties;
    }

    /**
     * Resets all of the records values
     */
    public function reset() {
        foreach($this->getProperties() as $prop) {
            $this->$prop = null;
        }
    }

    /**
     * Helper function for iflection
     *
     * @return \ReflectionProperty[]
     */
    protected function getProperties() {
        $reflect = new \ReflectionClass($this);
        return $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);
    }
}