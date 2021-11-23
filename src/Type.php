<?php

namespace JbSchmitt\model;

class Type {

    const NOT_SUPPORTED = 0;

    const CHAR = 1;
    const VARCHAR = 2;
    const TINYTEXT = 3;
    const TEXT = 4;
    const BLOB = 5;
    const MEDIUMTEXT = 6;
    const MEDIUMBLOB = 7;
    const LONGTEXT = 8;
    const LONGBLOB = 9;
    
    const BOOLEAN = 100;

    const BIT = 101;
    const TINTYINT = 102;
    const SMALLINT = 103;
    const MEDIUMINT = 104;
    const INT = 105;
    const BIGINT = 106;
    
    const FLOAT = 201;
    const DOUBLE = 202;
    const DECIMAL = 203;
    
    const DATE = 301;
    const DATETIME = 302;
    const TIMESTAMP = 303;
    const TIME = 304;
    const YEAR = 305;

    const ENUM = 401;
    const SET = 402;
}