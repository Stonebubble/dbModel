<?php

namespace JbSchmitt\model;

use DateTime;
use InvalidArgumentException;

/**
 * Cast
 */
class Cast {
    
    const VARCHAR = "string";
    const TEXT = "string";
    const JSON = "string";

    const SERIAL = "int";
    const INT = "int";
    const TIMESTAMP = "timestamp";
    const DATETIME = 'datetime';
    const BOOL = "bool";
    
    /**
     * __construct
     *
     * @return void
     */
    private function __construct() {
    }
    
    /**
     * cast
     *
     * @param  string $name     Cast::Type
     * @param  mixed  $variable variable to cast
     * @return void
     */
    public static function cast(string $name, $variable) {
        switch ($name) {
            case "string":
                return (string) $variable;
                break;
            case "int":
                if (!is_int($variable))
                    throw new InvalidArgumentException("Not an int");
                return (int) $variable;
                break;
            case "float":
                if (!is_numeric($variable))
                    throw new InvalidArgumentException("Not a float");
                return (float) 0.0 + $variable;
                break;
            case "bool":
                return (bool) $variable;
                break;
            case "datetime":
                if ($variable instanceof DateTime)
                    return $variable;
                return new DateTime($variable);
            
            default:
                throw new InvalidArgumentException("Type not supported!");
                break;
        }
    }
}
