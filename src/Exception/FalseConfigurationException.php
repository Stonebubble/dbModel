<?php

namespace JbSchmitt\model\Exception;

use Exception;
use Throwable;

/**
 * False Configuration Exception fires 
 * when the given configuration is false or doesn't exist
 */
class FalseConfigurationException extends Exception {

    public function __construct(string $message = "", $code = 0, ?Throwable $previous = NULL) {
        parent::__construct($message, $code, $previous);
    }
}
