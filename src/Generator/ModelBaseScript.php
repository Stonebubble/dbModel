<?php

namespace JbSchmitt\model\Generator;

use JbSchmitt\model\Column;
use JbSchmitt\model\Type;

class ModelBaseScript {

    /**
     * __construct
     *
     * @param  mixed $databaseName database name
     * @param  mixed $tableName    table name
     * @return void
     */
    public function __construct(private string $databaseName, private string $tableName) { }


    private const SQL_FOREIGN_LINK = "SELECT * from INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = '%s' AND REFERENCED_TABLE_NAME = '%s'";

    const START = <<<PHP
        <?php

        /**
         * auto generated base model of '%s'
         * 
         * @author Jakob Schmitt
         * @version 1.0.0
         */

        namespace TicketTool\Model\Base;

        use JbSchmitt\model\Model;
        use JbSchmitt\model\ModelDB;
        use TicketTool\Model\Table\\%sTable;

        /**
         * Base functionallity of getter and setter for model of '%s'
         */
        class %sBase extends Model {
            const TABLE = %sTable::class;

        %s
        }
        PHP;

    /**
     * output
     *
     * @return string
     */
    public function output():string {
        return sprintf(
            self::START, 
            $this->tableName,
            ucfirst($this->tableName),
            $this->tableName, 
            ucfirst($this->tableName),
            ucfirst($this->tableName), 
            $this->notNull() . "\n\n" .
            $this->getterSetter() . "\n\n" .
            $this->update()
        );
    }
    
    /**
     * creates or replaces file
     *
     * @param string $dir
     * @return void
     */
    public function createFile(string $dir) {
        file_put_contents($dir . "/" . ucfirst($this->tableName)."Base.php", $this->output());
    }

    private function getPK() {

    }

    private function getterSetter() {
        $table = "TicketTool\Model\Table\\" . ucfirst($this->tableName) . "Table";
        $getterSetterString = "    // getter and setter";
        foreach ($table::columns() as $key => $column) {
            $getterSetterString .= "\n" . $this->_get($column) . "\n" . $this->_set($column);
        }
        return $getterSetterString;
    }
    
    /**
     * get
     *
     * @param  Column $column a column
     * @return void
     */
    private function _get(Column $column) {
        $getString = <<<PHP

            /**
             * get value from %s
             * 
             * @return %s
             */
            public function get%s() {
                if (array_key_exists('%s',\$this->data)) {
                    return \$this->data['%s'];
                }
                return %s;
            }
        PHP;
        if ($column->defaultValue === NULL) {
            $default = 'NULL';
        } elseif (is_numeric($column->defaultValue)) {
            $default = $column->defaultValue;
        } else {
            $default = "'" . addslashes($column->defaultValue) . "'";
        }
        return sprintf(
            $getString,
            $column->name,
            $column->name,
            self::columnNameToCamelCase($column->name),
            $column->name,
            $column->name,
            $default
        );
    }
    
    /**
     * set
     *
     * @param  Column $column a column
     * @return void
     */
    private function _set(Column $column) {
        $name = self::columnNameToCamelCase($column->name);
        $type = Type::toPhpType($column->type);
        $getString = <<<PHP

            /**
             * set the value for {$column->name}
             * 
             * @param %s\$%s {$column->name}
             * @return void
             */
            public function set$name(%s\$%s):void {
                \$this->data['%s'] = \$%s;
                \$this->modifiedColumns['%s'] = TRUE;
            }
        PHP;
        return sprintf(
            $getString,
            $type != "" ? $type . " " : "",
            lcfirst($name),
            $type != "" ? $type . " " : "",
            lcfirst($name),
            $column->name,
            lcfirst($name),
            $column->name
        );
    }
    
    /**
     * removes "_" and CamelCase the letter after that
     *
     * @param  mixed $string column name
     * @return string
     */
    private static function columnNameToCamelCase(string $string) {
        return implode("", array_map('ucfirst', preg_split("/\_/", $string)));
    }

    //TODO generate auto getRelatedObjects
    
    private function notNull() {
        $null = <<<PHP

            /**
             * checks if NOT NULL columns are NULL
             *
             * @return bool
             */
            public function isNotNullValues() {
                return (
                    %s
                );
            }
        PHP;
        $table = "TicketTool\Model\Table\\" . ucfirst($this->tableName) . "Table";
        $nullString = [];
        foreach ($table::columns() as $key => $value) {
            if (!$value->isNull) {
                $nullString[] = "!isset(\$this->data['$value->name']) || is_null(\$this->data['$value->name'])"; 
            }
            
        }
        return sprintf($null, implode("\n            ||", $nullString));
    }

    private function find() {
        return <<<PHP
            public static function find(\$id) {
                
            }
        PHP;
    }

    private function update() {
        $table = "TicketTool\Model\Table\\" . ucfirst($this->tableName) . "Table";
        $func = function($value) {
            return $value . " = ?";
        };
        $where = "WHERE " . implode(" AND ", array_map($func, $table::primaryKey()));
        $primaries = implode("", array_map(fn($p) => "\$modifiedColumns[] = \$this->data['$p'];\n", $table::primaryKey()));
        return <<<PHP
            /**
             * updates modified values in db
             *
             * @return int
             */
            public function update():int {
                if (empty(\$this->modifiedColumns))
                    return 0;
                \$modifiedColumns = [];
                foreach (\$this->modifiedColumns as \$key => \$value) {
                    \$modifiedColumns[] = \$this->data[\$key];
                }
                \$set = implode(", ", array_map(fn(\$value) => \$value . " = ?", array_keys(\$this->modifiedColumns)));
                \$query = 'UPDATE {$this->tableName} SET ' . \$set . " $where";
                $primaries
                var_dump(array_keys(\$this->modifiedColumns));
                var_dump(\$query);
                \$update = ModelDB::exec(\$query, \$modifiedColumns);
                \$this->modifiedColumns = [];
                return \$update;
            }
        PHP;
    }
}
