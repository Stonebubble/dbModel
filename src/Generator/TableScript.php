<?php 

namespace JbSchmitt\model\Generator;

use JbSchmitt\model\ModelDB;

/**
 * TableScript
 */
class TableScript  {
    
    /**
     * __construct
     *
     * @param  mixed $databaseName
     * @param  mixed $tableName
     * @return void
     */
    public function __construct(private string $databaseName, private string $tableName) { }
    
    private const SQL_TABLES = "SHOW COLUMNS FROM `%s`;";
    private const SQL_FOREIGN_KEYS = "SELECT * from INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = '%s' AND TABLE_NAME = 'reservation' AND REFERENCED_TABLE_NAME IS NOT NULL";

    const START = <<<PHP
        <?php

        /**
         * auto generated table map of '%s'
         * 
         * @author Jakob Schmitt
         * @version 1.0.0
         */

        namespace TicketTool\Model\Table;

        use JbSchmitt\model\Column;
        use JbSchmitt\model\Table;
        use JbSchmitt\model\Type;

        /**
         * 
         */
        class %sTable extends Table {
            const TABLE = "%s";
            private static \$self;
            
            /**
             * private constructor because its a static class
             *
             * @return void
             */
            private function __construct() {
            %s
            }
            
            /**
             * columns of this table
             *
             * @return void
             */
            public static function columns() {
                if (empty(self::\$self)) {
                    static::\$self = new self();
                }
                return static::\$self->COLUMNS;
            }

            public static function pk() {
                if (empty(self::\$self)) {
                    static::\$self = new self();
                }
                return static::\$self->PK;
            }

        }
        PHP;
    
    /**
     * output
     *
     * @return void
     */
    public function output() {
        return sprintf(self::START, $this->tableName, ucfirst($this->tableName), $this->tableName, $this->columns());
    }

    public function createFile(string $dir) {
        file_put_contents($dir . "/" . ucfirst($this->tableName)."Table.php", $this->output());
    }

    private function columns() {
        $tableData = ModelDB::selecetAll(sprintf(self::SQL_TABLES, $this->tableName));
        $fkData = ModelDB::selecetAll(sprintf(self::SQL_FOREIGN_KEYS, $this->databaseName));
        $cols = <<<PHP
            \$this->COLUMNS = [
                                            // col        type      null    size default PK
        %s
                ];

                /**
                 * Primary key columns reference
                 */
                \$this->PK = [
        %s
                ];
        PHP;

        $string = "";
        $pkString = "";
        foreach ($tableData as $key => $value) {
            $type = "Type::" . strtoupper(strtok($value['Type'], '('));
            $size = substr(substr($value['Type'], strpos($value['Type'], "(")), 1, -1);
            $size = is_numeric($size) ? $size : "NULL";
            $isNotNUll = ($value['Null'] == "NO") ? "FALSE" : "TRUE";
            $default = $value['Default'] == NULL ? 'NULL' : '"' . $value['Default'] . '"';
            $isPk = $value['Key'] == "PRI" ? "TRUE" : "FALSE";
            $fkTable = "NULL";
            $fkColumn = "NULL";

            // foreign key constraint
            foreach ($fkData as $key => $fkValue) {
                if ($fkValue['COLUMN_NAME'] == $value['Field']) {
                    $fkTable = '"' . $fkValue['REFERENCED_TABLE_NAME'] . '"';
                    $fkColumn = '"' . $fkValue['REFERENCED_COLUMN_NAME'] . '"';
                    unset($fkData[$key]);
                }
            }

            if ($isPk == "TRUE") {
                $pkString .= <<<PHP
                            '{$value['Field']}',
                PHP;
            }

            $string .= <<<PHP
                        '{$value['Field']}' => new Column("{$value['Field']}", $type, $isNotNUll, $size, $default, $isPk, $fkTable, $fkColumn),\n
            PHP;
        }
        return sprintf($cols, $string, $pkString);
    }
}
