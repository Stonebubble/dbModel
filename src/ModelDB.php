<?php

namespace JbSchmitt\model;

use Delight\Db\Database;
use Delight\Db\ErrorHandler;
use Delight\Db\PdoDataSource;
use Delight\Db\Profiler;
use Delight\Db\Throwable\BeginTransactionFailureException;
use Delight\Db\Throwable\CommitTransactionFailureException;
use Delight\Db\Throwable\IntegrityConstraintViolationException;
use Delight\Db\Throwable\RollBackTransactionFailureException;
use Delight\Db\Throwable\TransactionFailureException;
use PDO;
use PDOException;

/**
 * ModelDB.
 * Safe and convenient SQL database access in a driver-agnostic way
 */
class ModelDB {
    
    private static PDO $pdo;
    private static Database $db;
    
    /**
     * initializes values once (already done)
     *
     * @return void
     */
    public static function constructStatic($pathToConfig) {
        if (isset(self::$pdo))
            return;
            
        $c = include $pathToConfig;
        $dsn = "mysql:host=" . $c['db_host'] . ";dbname=" . $c['db_name'] . ";charset=" . $c['db_charset'] . ";";
        try {
            self::$pdo = new PDO($dsn, 
            $c['db_username'],
            $c['db_password'],
            [
                PDO::ATTR_PERSISTENT => TRUE,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_CLASS
            ]);
        } catch (PDOException $e) {
            ErrorHandler::rethrow($e);
        }
        self::$db = \Delight\Db\PdoDatabase::fromDsn(
            new \Delight\Db\PdoDsn(
                "mysql:dbname={$c['db_name']};host={$c['db_host']};charset={$c['db_charset']};",
                $c['db_username'],
                $c['db_password']
            )
        );

    }
        
    /**
     * _selectInternal
     *
     * @param  callable $callback
     * @param  string   $query
     * @param  array    $bindValues
     * @return void
     */
    private static function _selectInternal(callable $callback, string $query, array $bindValues = NULL) {
        try {
            // create a prepared statement from the supplied SQL string
            $stmt = static::$pdo->prepare($query);
        } catch (PDOException $e) {
            ErrorHandler::rethrow($e);
        }

        /** @var PDOStatement $stmt */

        // bind the supplied values to the query and execute it
        try {
            $stmt->execute($bindValues);
        } catch (PDOException $e) {
            ErrorHandler::rethrow($e);
        }

        // fetch the desired results from the result set via the supplied callback
        $results = $callback($stmt);

        // if the result is empty
        if (empty($results) && $stmt->rowCount() === 0 && ($this->driverName !== PdoDataSource::DRIVER_NAME_SQLITE || \is_bool($results) || \is_array($results))) {
            // consistently return `null`
            return NULL;
        }
        // if some results have been found
        // return these as extracted by the callback
        return $results;
        
    }

    /**
     * Selects from the database using the specified query and returns all rows and columns
     *
     * You should not include any dynamic input in the query
     *
     * Instead, pass `?` characters (without any quotes) as placeholders and pass the actual values in the second argument
     *
     * @param string     $query      the SQL query to select with
     * @param array|null $bindValues (optional) the values to bind as replacements for the `?` characters in the query
     * @param Class      $class      class to be fetched
     * @return static object of 
     */
    public static function selectObject(string $query, ?array $bindValues = NULL, $class) {
        return self::_selectInternal(function ($stmt) use (&$class) {
            return $stmt->fetchObject($class);
        }, $query, $bindValues);
    }

    /**
     * Selects from the database using the specified query and returns all rows and columns
     *
     * You should not include any dynamic input in the query
     *
     * Instead, pass `?` characters (without any quotes) as placeholders and pass the actual values in the second argument
     *
     * @param string     $query      the SQL query to select with
     * @param array|null $bindValues (optional) the values to bind as replacements for the `?` characters in the query
     * @param Class      $class      class to be fetched
     * @return static object of 
     */
    public static function selectAllObjects(string $query, ?array $bindValues = NULL, $class) {
        return self::_selectInternal(function ($stmt) use (&$class) {
            return $stmt->fetchAll(PDO::FETCH_CLASS, $class);
        }, $query, $bindValues);
    }
    
    /**
     * selecetAll
     *
     * @param  mixed $query      the SQL query to select with
     * @param  mixed $bindValues optional the values to bins as replacements for the `?`
     * @return array
     */
    public static function selecetAll(string $query, ?array $bindValues = NULL) {
        return self::$db->select($query, $bindValues);
    }
    
    /**
     * selectGroup 
     *
     * @param  mixed $query      the SQL query to select with
     * @param  mixed $bindValues values to replace ?
     * @return array
     */
    public static function selectGroup(string $query, ?array $bindValues = NULL) {
        return self::_selectInternal(function ($stmt) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_GROUP);
        }, $query, $bindValues);
    }


    /**
     * Selects from the database using the specified query and returns the value of the first column in the first row
     *
     * You should not include any dynamic input in the query
     *
     * Instead, pass `?` characters (without any quotes) as placeholders and pass the actual values in the second argument
     *
     * @param string $query the SQL query to select with
     * @param array|null $bindValues (optional) the values to bind as replacements for the `?` characters in the query
     * @return mixed|null the value of the first column in the first row returned by the server or `null` if no results have been found
     */
    public static function selectValue($query, array $bindValues = NULL) {
        return self::$db->selectValue($query, $bindValues);
    }

    /**
     * Selects from the database using the specified query and returns the first row
     *
     * You should not include any dynamic input in the query
     *
     * Instead, pass `?` characters (without any quotes) as placeholders and pass the actual values in the second argument
     *
     * @param string $query the SQL query to select with
     * @param array|null $bindValues (optional) the values to bind as replacements for the `?` characters in the query
     * @return array|null the first row returned by the server or `null` if no results have been found
     */
    public static function selectRow($query, array $bindValues = NULL) {
        return self::$db->selectRow($query, $bindValues);
    }

    /**
     * Selects from the database using the specified query and returns the first column
     *
     * You should not include any dynamic input in the query
     *
     * Instead, pass `?` characters (without any quotes) as placeholders and pass the actual values in the second argument
     *
     * @param string $query the SQL query to select with
     * @param array|null $bindValues (optional) the values to bind as replacements for the `?` characters in the query
     * @return array|null the first column returned by the server or `null` if no results have been found
     */
    public static function selectColumn($query, array $bindValues = NULL) {
        return self::$db->selectColumn($query, $bindValues);
    }

    /**
     * Inserts the given mapping between columns and values into the specified table
     *
     * @param string|string[] $tableName the name of the table to insert into (or an array of components of the qualified name)
     * @param array $insertMappings the mappings between columns and values to insert
     * @return int the number of inserted rows
     * @throws IntegrityConstraintViolationException
     */
    public static function insert($tableName, array $insertMappings) {
        return self::$db->insert($tableName, $insertMappings);
    }

    /**
     * Updates the specified table with the given mappings between columns and values
     *
     * @param string|string[] $tableName the name of the table to update (or an array of components of the qualified name)
     * @param array $updateMappings the mappings between columns and values to update
     * @param array $whereMappings the mappings between columns and values to filter by
     * @return int the number of updated rows
     * @throws IntegrityConstraintViolationException
     */
    public static function update($tableName, array $updateMappings, array $whereMappings) {
        return self::$db->update($tableName, $updateMappings, $whereMappings);
    }

    /**
     * Deletes from the specified table where the given mappings between columns and values are found
     *
     * @param string|string[] $tableName the name of the table to delete from (or an array of components of the qualified name)
     * @param array $whereMappings the mappings between columns and values to filter by
     * @return int the number of deleted rows
     */
    public static function delete($tableName, array $whereMappings) {
        return self::$db->delete($tableName, $whereMappings);
    }

    /**
     * Executes an arbitrary statement and returns the number of affected rows
     *
     * This is especially useful for custom `INSERT`, `UPDATE` or `DELETE` statements
     *
     * You should not include any dynamic input in the statement
     *
     * Instead, pass `?` characters (without any quotes) as placeholders and pass the actual values in the second argument
     *
     * @param string $statement the SQL statement to execute
     * @param array|null $bindValues (optional) the values to bind as replacements for the `?` characters in the statement
     * @return int the number of affected rows
     * @throws IntegrityConstraintViolationException
     * @throws TransactionFailureException
     */
    public static function exec($statement, array $bindValues = NULL) {
        return self::$db->exec($statement, $bindValues);
    }

    /**
     * Returns the ID of the last row that has been inserted or returns the last value from the specified sequence
     *
     * @param string|null $sequenceName (optional) the name of the sequence that the ID should be returned from
     * @return string|int the ID or the number from the sequence
     */
    public static function getLastInsertId($sequenceName = NULL) {
        return self::$db->getLastInsertId($sequenceName);
    }

    /**
     * Starts a new transaction and turns off auto-commit mode
     *
     * Changes won't take effect until the transaction is either finished via `commit` or cancelled via `rollBack`
     *
     * @throws BeginTransactionFailureException
     */
    public static function beginTransaction() {
        return self::$db->beginTransaction();
    }

    /**
     * Alias of `beginTransaction`
     *
     * @throws BeginTransactionFailureException
     */
    public static function startTransaction() {
        return self::$db->startTransaction();
    }

    /**
     * Returns whether a transaction is currently active
     *
     * @return bool
     */
    public static function isTransactionActive() {
        return self::$db->isTransactionActive();
    }

    /**
     * Finishes an existing transaction and turns on auto-commit mode again
     *
     * This makes all changes since the last commit or roll-back permanent
     *
     * @throws CommitTransactionFailureException
     */
    public static function commit() {
        return self::$db->commit();
    }

    /**
     * Cancels an existing transaction and turns on auto-commit mode again
     *
     * This discards all changes since the last commit or roll-back
     *
     * @throws RollBackTransactionFailureException
     */
    public static function rollBack() {
        return self::$db->rollBack();
    }

    /**
     * Returns the performance profiler currently used by this instance (if any)
     *
     * @return Profiler|null
     */
    public static function getProfiler() {
        return self::$db->getProfiler();
    }

    /**
     * Sets the performance profiler used by this instance
     *
     * This should only be used during development and not in production
     *
     * @param Profiler|null $profiler the profiler instance or `null` to disable profiling again
     * @return static this instance for chaining
     */
    public static function setProfiler(Profiler $profiler = NULL) {
        return self::$db->setProfiler($profiler);
    }

    /**
     * Returns the name of the driver that is used for the current connection
     *
     * @return string
     */
    public static function getDriverName() {
        return self::$db->getDriverName();
    }

    /**
     * Quotes an identifier (e.g. a table name or column reference)
     *
     * This allows for special characters and reserved keywords to be used in identifiers
     *
     * There is usually no need to call this method
     *
     * Identifiers should not be set from untrusted user input and in most cases not even from dynamic expressions
     *
     * @param string $identifier the identifier to quote
     * @return string the quoted identifier
     */
    public static function quoteIdentifier($identifier) {
        return self::$db->quoteIdentifier($identifier);
    }

    /**
     * Quotes a table name
     *
     * This allows for special characters and reserved keywords to be used in table names
     *
     * There is usually no need to call this method
     *
     * Table names should not be set from untrusted user input and in most cases not even from dynamic expressions
     *
     * @param string|string[] $tableName the table name to quote (or an array of components of the qualified name)
     * @return string the quoted table name
     */
    public static function quoteTableName($tableName) {
        return self::$db->quoteTableName($tableName);
    }

    /**
     * Quotes a literal value (e.g. a string to insert or to use in a comparison) or an array thereof
     *
     * This allows for special characters to be used in literal values
     *
     * There is usually no need to call this method
     *
     * You should always use placeholders for literal values and pass the actual values to bind separately
     *
     * @param string $literal the literal value to quote
     * @return string the quoted literal value
     */
    public static function quoteLiteral($literal) {
        return self::$db->quoteLiteral($literal);
    }

    /**
     * Adds a listener that will execute as soon as the database connection has been established
     *
     * If the database connection has already been active before, the listener will execute immediately
     *
     * @param callable $onConnectListener the callback to execute
     * @return static this instance for chaining
     */
    public static function addOnConnectListener(callable $onConnectListener) {
        return self::$db->addOnConnectListener($onConnectListener);
    }

}