<?php

namespace JbSchmitt\model;

use BadMethodCallException;
use InvalidArgumentException;
use JbSchmitt\model\Exception\NullValuesException;
use JsonSerializable;

abstract class Model implements JsonSerializable {
    protected static string $primary_key = "id";

    protected array $relatedModels = [];
    protected array $data = [];
    protected bool $isNew = TRUE;
    protected array $modifiedColumns = [];

    private const LINKEDSQL = "SELECT * from %s WHERE %s = ?";



    /**
     * new Object is created by calling ::create([...])
     *
     * @param array $array paramater like in fromArray
     * @param bool  $new   if element exists in db
     * @return void
     */
    public function __construct(array $array = NULL, bool $new = TRUE) {
        $this->data[static::$primary_key] = NULL;
        if ($array === NULL)
            return;

        if (isset($array[0])) {
            $array = $array[0];
        }
        $this->isNew = $new;
        $this->fromArray($array);
    }
    
    /**
     * fromArray
     *
     * @param  mixed $array paramert
     * @return void
     */
    public function fromArray(array $array) {
/*        foreach (static::$foreigns as $key => $value) {
            if (array_key_exists($key, $array)) {
                if ($array[$key] instanceof $value[1]) {
                    $this->relatedModels[$key] = $array[$key];
                    $this->data[$value[0]] = $array[$key]->getPK();
                }
                unset($array[$key]);
            }
        } */
        $this->data = $array + $this->data;
        $this->data = array_intersect_key($this->data, static::TABLE::columns());
    }
    
    /**
     * returns primaryKey
     *
     * @return void
     */
    public function getPK() {
        return $this->data[static::$primary_key];
    }


    /**
     * get validation Rules
     *
     * @return array
     */
    protected static function getTableName():string {
        return static::$table;
    }

    
    /**
     * jsonSerialize
     *
     * @return void
     */
    public function jsonSerialize() {
        $jsonArray = $this->toArray(TRUE);
        foreach (static::$foreigns as $key => $value) {
            unset($jsonArray[$value[0]]);
        }
        return $jsonArray;
    }
    
    /**
     * toArray
     *
     * @param  mixed $includeObjects
     * @return array
     */
    public function toArray(bool $includeObjects = FALSE) {
        $data = $this->data;
        if ($includeObjects === TRUE) {
            foreach (static::$foreigns as $key => $value) {
                if (isset($this->relatedModels[$key])) {
                    $data[$key] = $this->relatedModels[$key]->toArray();
                }
            }
        }
        return $data;
    }

    public static function where($whereMappings):ModelCollection {
        // prepare a list for the individual predicates of the `WHERE` clause
        $wherePredicates = [];
        $bindValues = [];

        // for each mapping of a column name to its respective value to filter by
        foreach ($whereMappings as $whereColumn => $whereValue) {
            // create an individual predicate with the column name and a placeholder for the value
            $wherePredicates[] = $whereColumn . ' = ?';
            // and remember which value to bind here
            $bindValues[] = $whereValue;
        }

        // build the full statement (still using placeholders)
        $sql = "SELECT * from " . static::TABLE::TABLE . ' WHERE ' . implode(' AND ', $wherePredicates) . ';';
        return new ModelCollection(static::class, ModelDB::selecetAll($sql, $bindValues));
    }

    
    private function getLinkedForeign($class) {
        $sql = sprintf(static::LINKEDSQL, $class, static::$linkedForeigns[$class]);

        $reservations = ModelDB::selecetAll($sql, [$this->data[static::$primary_key]]);
        $return = [];

        foreach ($reservations as $value) {
            $return[] = new $class($value, FALSE);
        }
        return $return;
    }


    /**
     * find by id
     *
     * @param  mixed $pk      id
     * @param  array       $columns columns
     * @return obj
     */
    public static function find(mixed $pk, $columns = ['*']) {
    }
    
    
    /**
     * inserts this object into db
     *
     * @return bool
     */
    public function insert():bool {
        try {
            if ($this->isNotNullValues())
                throw new NullValuesException("Some values are null although this isn't allowed", 1);
                
            $insert = ModelDB::insert(static::TABLE::TABLE, $this->toArray()) > 0;
            $id = (int) ModelDB::getLastInsertId();
            if (!empty($id))
                $this->data[static::$primary_key] = $id;
            return $insert;
        } catch (\Delight\Db\Throwable\Error $th) {
            var_dump($th);
            return FALSE;
        }
    }
    
    /**
     * inserts new Object or updates existing one
     *
     * @return bool false if nothing to update
     */
    public function save():bool {
        if ($this->isNew) {
            return $this->insert();
        }
        return $this->update();
    }

    public function push():bool {
        foreach ($this->relatedModels as $key => $value) {
            $value->save();
            $column = static::$foreigns[$key][0];
            if (empty($this->data[$column])) {
                $this->data[$column] = $value->getPK();
            }
        }
    }
    
    
    /**
     * updates modified values in db
     *
     * @return int
     */
    public function update():int {
        if (empty($this->modifiedColumns))
            return 0;
        $modifiedColumns = array_intersect_key($this->data, $this->modifiedColumns);
        $where = array_intersect_key($this->data, static::TABLE::primaryKey());
        var_dump(static::TABLE::primaryKey());
        var_dump($where);
        $update = ModelDB::update(static::TABLE::TABLE, $modifiedColumns, 
            $where);
        $this->modifiedColumns = [];
        return $update;
    }
    
    /**
     * deletes entry in db
     *
     * @param  id|array|string $params primary key/array
     * @return bool on success
     */
    public static function delete($params):bool {
        if (is_int($params) || is_string($params)) {
            $where = [static::$primary_key => $params];
        } else if (is_array($params)) {
            $where = $params;
        } else {
            throw new InvalidArgumentException("must be of type string, int or array");
        }
        return ModelDB::delete(static::$table, $where) > 0;
    }

}