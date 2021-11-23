<?php

namespace JbSchmitt\model;

use InvalidArgumentException;
use JsonSerializable;

/**
 * ModelCollection
 */
class ModelCollection implements JsonSerializable {
    protected $array;
    protected $className;
        
    /**
     * new Collection which holds multiple instances of a Model
     *
     * @param  mixed $className the type
     * @param  mixed $modelArray
     * @return void
     * @throws InvalidArgumentException if $class isn't of type Model
     */
    public function __construct($className, ?array $modelArray = []) {
        if (!($className instanceof Model))
            throw new InvalidArgumentException($className . " is not of type Model");

            $this->array = $modelArray;
            $this->className;
    }
    
    /**
     * toArray
     *
     * @return array
     */
    public function toArray():array {
        return $this->array;
    }
    
    /**
     * toModels
     *
     * @return array
     */
    public function toModels():array {
        $data = [];
        foreach ($this->array as $value) {
            $data[] = new $this->className($value);
        }
        return $data;
    }
    
    /**
     * class of 
     *
     * @return Model
     */
    public function getClass():Model {
        return $this->className::class;
    }
    
    
    /**
     * isEmpty
     *
     * @return bool
     */
    public function isEmpty():bool {
        return empty($this->data);
    }
    
    /**
     * jsonSerialize
     *
     * @return void
     */
    public function jsonSerialize() {
        return $this->array;
    }
    
    /**
     * count
     *
     * @return void
     */
    public function count() {
        return count($this->array);
    }
}
