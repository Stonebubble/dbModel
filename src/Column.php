<?php

namespace JbSchmitt\model;

/**
 * Column of a database table for the model mapping
 */
class Column {
    
    /**
     * __construct
     *
     * @param  mixed $name         column name
     * @param  mixed $type         type
     * @param  mixed $isNotNull    is not null
     * @param  mixed $size         size
     * @param  mixed $defaultValue default value
     * @param  mixed $pk           is primary key
     * @param  mixed $fkTable      foreign key table
     * @param  mixed $fkColumn     foreing key column
     * @return void
     */
    public function __construct(
        public string $name, 
        public int $type, 
        public bool $isNotNull = FALSE, 
        public ?int $size = NULL, 
        public $defaultValue = NULL, 
        public bool $pk = FALSE, 
        public ?string $fkTable = NULL, 
        public $fkColumn = NULL) { }
}
