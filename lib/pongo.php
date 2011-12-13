<?php
namespace pongo;

/**
 * a simple ORM class for Mongo database
 *
 *
 */
class Pongo {
    /**
     * @var Mongo, MongoDB, MongoCollection
     */
    private static $mongo = null;
    private static $mongoDB = null;
    private static $mongoCollection = null;

    /**
     * collections list
     *
     * @var array
     */
    private static $collections = array();

    /**
     * fields with its values need to save/update
     * @var array
     */
    protected $fields = array();

    public function __construct(Array $fields = array()) 
    {
        $this->processFields($fields);
    }

    /**
     * initialize mongo connection
     *
     * @param string $server
     * @param array $options
     */
    public static function initializeConnection($server, $database, Array $options = array()) 
    {
        self::$mongo = new \Mongo($server, $options);
        self::$mongoDB = self::$mongo->selectDB($database);
    }

    /**
     * process fields
     *
     * @param array $fields
     */
    public function processFields(Array $fields)
    {
        if (array_diff(array_keys($fields), static::$FIELDS)) {
            throw new PongoFieldsMatcheError();
        }
        $this->fields = $fields;
    }

    /**
     * insert
     *
     * @param array $options
     *
     * @return bool|array 
     */
    public function insert(Array $options = array())
    {
        return $this->mongoCollection()->insert($this->fields, $options); 
    }

    /**
     * save the chanage
     *
     * @param array $options
     *
     * @return mixed
     */
    public function save(Array $options = array())
    {
        return $this->mongoCollection()->save($this->fields, $options); 
    }

    /**
     * remove the current item
     *
     * @param array $options
     *
     * @return bool|array
     */
    public function remove(Array $options = array())
    {
        $options = array_merge(array(
            'justOne' => true
        ), $options);

        return $this->mongoCollection()->remove(array(
            '_id' => $this->fields['_id']
        ), $options); 
    }

    /**
     * get mongoCollection, initialize it if it's first time access it.
     *
     * @return MongoCollection
     */
    protected function mongoCollection() 
    {
        if (null === self::$mongoCollection) {
            self::$mongoCollection = self::$mongoDB->selectCollection(static::$TABLE);
        }

        return self::$mongoCollection;
    }

    /**
     * drop the collection
     *
     * @return array  database responses
     */
    public static function drop()
    {
        self::collection()->drop();
    }

    /**
     * get mongoCollection of the caller
     *
     * @return MongoCollection
     */
    public static function DB()
    {
        self::collection();
        return self::$mongoDB;
    }

    /**
     * get mongoCollection of the caller
     *
     * @return MongoCollection
     */
    public static function collection()
    {
        if (isset(self::$collections[static::$TABLE]) && 
            self::$collections[static::$TABLE] instanceOf \MongoCollection) {
            return self::$collections[static::$TABLE];
        }

        $pongoCollection = new static();
        return self::$collections[static::$TABLE] = $pongoCollection->mongoCollection();
    }

    /**
     * count
     *
     * @param array $conditions
     *
     * return int number of items
     */
    public static function count(Array $conditions = array())
    {
        return self::collection()->find($conditions)->count();
    }

    /**
     * find one item by the given conditions
     *
     * @param array $conditions
     * @param array $selectFields
     *
     * @return Pongo (subclass of Pongo), null if nothing found
     */
    public static function findOne(Array $conditions = array(), Array $selectFields = Array()) 
    {
        $result = self::collection()->findOne($conditions, $selectFields);

        if ($result == null) {
            return null;
        }

        return new static($result);
    }

    /**
     * find items 
     *
     * @param array $conditions
     * @param array $selectFields
     *
     * @return MongoCursor 
     */
    public static function mongoFind(Array $conditions = array(), Array $selectFields = Array()) 
    {
        return self::collection()->find($conditions, $selectFields);
    }

    /**
     * find items
     *
     * @param array $params
     *
     * @return Array contains 0 or more objects
     */
    public static function find(Array $params = array()) 
    {
        $conditions = isset($params['conditions']) ? $params['conditions'] : array();
        $selectFields = isset($params['fields']) ? $params['fields'] : array(); 

        $mongoCursor = self::collection()->find($conditions, $selectFields);

        if (isset($params['sort'])) {
            $mongoCursor->sort($params['sort']);
        }

        if (isset($params['skip'])) {
            $mongoCursor->skip($params['skip']);
        }

        if (isset($params['limit'])) {
            $mongoCursor->limit($params['limit']);
        }

        return self::asObjects($mongoCursor); 
    }

    /**
     * convert the results of MongoCursor to Objects
     *
     * @param MongoCursor $mongoCursor
     *
     * @return array
     */
    public static function asObjects(\MongoCursor $mongoCursor)
    {
        $objects = array();
        foreach ($mongoCursor as $item) {
           $objects[] = new static($item); 
        }

        return $objects;
    }

    /**
     * get a value of the field
     *
     * @param string $field
     *
     * @return mixed
     */
    public function __get($field)
    {
        $this->validateField($field); 
        return $this->fields[$field];
    }

    /**
     * set field 
     *
     * @param string $field
     * @param mixed $value
     */
    public function __set($field, $value)
    {
        $this->validateField($field); 
        $this->fields[$field] = $value;
    }

    /**
     * validate the given field, check if it's exists in collection 
     * the exception PongoInvalidFieldName will be thrown if the validation failed.
     *
     * @param string $field
     *
     * @return bool
     */
    private function validateField($field)
    {
        if (!isset($this->fields[$field])) {
            throw new PongoInvalidFieldName("Invalid field name given: " . $field);
        }

        return true;
    }
}

// some exceptions
class PongoFieldsMatcheError extends \Exception {}
class PongoInvalidFieldName extends \Exception {}
