<?php

/* ========================================================================
 * Open eClass 
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2014  Greek Universities Network - GUnet
 * A full copyright notice can be read in "/info/copyright.txt".
 * For a full list of contributors, see "credits.txt".
 *
 * Open eClass is an open platform distributed in the hope that it will
 * be useful (without any warranty), under the terms of the GNU (General
 * Public License) as published by the Free Software Foundation.
 * The full license can be read in "/info/license/license_gpl.txt".
 *
 * Contact address: GUnet Asynchronous eLearning Group,
 *                  Network Operations Center, University of Athens,
 *                  Panepistimiopolis Ilissia, 15784, Athens, Greece
 *                  e-mail: info@openeclass.org
 * ======================================================================== 
 */

define("DB_TYPE", "MYSQL");

require_once 'modules/db/dbhelper.php';

final class DBResult {

    var $lastInsertID;
    var $affectedRows;

    public function __construct($lastInsertID, $affectedRows) {
        $this->lastInsertID = $lastInsertID;
        $this->affectedRows = $affectedRows;
    }

}

final class Database {

    private static $REQ_LASTID = 1;
    private static $REQ_OBJECT = 2;
    private static $REQ_ARRAY = 3;
    private static $REQ_FUNCTION = 4;

    /**
     *
     * @var Hash map to store various databases
     */
    private static $dbs = array();

    /**
     * Get a database on its name: this is a static method
     * @param type $dbase The name of the database. Could be missing (or null) for the default database.
     * @return Database|null The database object
     */
    public static function get($dbase = null) {
        global $mysqlServer, $mysqlUser, $mysqlPassword, $mysqlMainDb;
        if (is_null($dbase))
            $dbase = $mysqlMainDb;
        if (array_key_exists($dbase, self::$dbs)) {
            $db = self::$dbs[$dbase];
        } else {
            try {
                $db = new Database($mysqlServer, $dbase, $mysqlUser, $mysqlPassword);
                self::$dbs[$dbase] = $db;
            } catch (Exception $e) {
                return null;
            }
        }
        return $db;
    }

    /**
     * @var PDO
     */
    private $dbh;

    /**
     * @param string $server
     * @param string $dbase
     * @param string $user
     * @param string $password
     */
    public function __construct($server, $dbase, $user, $password) {
        try {
            $params = null;
            switch (DB_TYPE) {
                case "POSTGRES":
                    $dsn = "pgsql:host=" . $server . ';dbname=' . $dbase;
                    break;
                case "MYSQL":
                    $dsn = 'mysql:host=' . $server . ';dbname=' . $dbase . ';charset=utf8';
                    $params = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8");
                    break;
                default :
                    Debug::message("Unknown database backend: " . DB_TYPE, Debug::ALWAYS);
            }
            $this->dbh = new PDO($dsn, $user, $password, $params);
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }

    /** This is a transactional version of queryNT.
     * 
     * @param string $statement a non-empty string forming the prepared function: use ? for variables bound to this statement
     * @param function $callback_error An *optional* argument with a callback function in case error trapping is required.
     * If the second argument is a callable, then this argument is handled as an error callback. If it is any other type (including null), then it is passed as a binding argument.
     * @param anytype $argument... A variable argument list of each binded argument
     * @return int Last inserted ID
     */
    public function query($statement) {
        return $this->queryI(func_get_args(), true);
    }

    /** This is a non-transactional version of query.
     * 
     * @param string $statement a non-empty string forming the prepared function: use ? for variables bound to this statement
     * @param function $callback_error An *optional* argument with a callback function in case error trapping is required.
     * If the second argument is a callable, then this argument is handled as an error callback. If it is any other type (including null), then it is passed as a binding argument.
     * @param anytype $argument... A variable argument list of each binded argument
     * @return int Last inserted ID
     */
    public function queryNT($statement) {
        return $this->queryI(func_get_args(), false);
    }

    private function queryI($args, $transactional) {
        $statement = $args[0];
        $offset = 1;
        $callback_error = $this->findErrorCallback($args, $offset);
        return $this->queryImpl($statement, $transactional, null, $callback_error, Database::$REQ_LASTID, array_slice($args, $offset));
    }

    /** This is a transactional version of queryFuncNT.
     *
     * @param string $statement a non-empty string forming the prepared function: use ? for variables bound to this statement
     * @param function $callback_function A function which as first argument gets an object constructed by each row of the result set. Can be null
     * @param function $callback_error An *optional* argument with a callback function in case error trapping is required.
     * If the third argument is a callable, then this argument is handled as an error callback. If it is any other type (including null), then it is passed as a binding argument.
     * @param anytype $argument... A variable argument list of each binded argument
     */
    public function queryFunc($statement, $callback_function) {
        return $this->queryFuncI(func_get_args(), true);
    }

    /** This is a non-transactional version of queryFunc.
     *
     * @param string $statement a non-empty string forming the prepared function: use ? for variables bound to this statement
     * @param function $callback_function A function which as first argument gets an object constructed by each row of the result set. Can be null
     * @param function $callback_error An *optional* argument with a callback function in case error trapping is required.
     * If the third argument is a callable, then this argument is handled as an error callback. If it is any other type (including null), then it is passed as a binding argument.
     * @param anytype $argument... A variable argument list of each binded argument
     */
    public function queryFuncNT($statement, $callback_function) {
        return $this->queryFuncI(func_get_args(), false);
    }

    private function queryFuncI($args, $transactional) {
        $statement = $args[0];
        $callback_function = $args[1];
        $offset = 2;
        $callback_error = $this->findErrorCallback($args, $offset);
        return $this->queryImpl($statement, $transactional, $callback_function, $callback_error, Database::$REQ_FUNCTION, array_slice($args, $offset));
    }

    /** This is a transactional version of queryArrayNT.
     *
     * @param string $statement a non-empty string forming the prepared function: use ? for variables bound to this statement
     * @param function $callback_error An *optional* argument with a callback function in case error trapping is required.
     * If the second argument is a callable, then this argument is handled as an error callback. If it is any other type (including null), then it is passed as a binding argument.
     * @param anytype $argument... A variable argument list of each binded argument
     * @return array An array of all objects as a result of this statement
     */
    public function queryArray($statement) {
        return $this->queryArrayI(func_get_args(), true);
    }

    /** This is a non-transactional version of queryArray.
     *
     * @param string $statement a non-empty string forming the prepared function: use ? for variables bound to this statement
     * @param function $callback_error An *optional* argument with a callback function in case error trapping is required.
     * If the second argument is a callable, then this argument is handled as an error callback. If it is any other type (including null), then it is passed as a binding argument.
     * @param anytype $argument... A variable argument list of each binded argument
     * @return array An array of all objects as a result of this statement
     */
    public function queryArrayNT($statement) {
        return $this->queryArrayI(func_get_args(), false);
    }

    private function queryArrayI($args, $transactional) {
        $statement = $args[0];
        $offset = 1;
        $callback_error = $this->findErrorCallback($args, $offset);
        return $this->queryImpl($statement, $transactional, null, $callback_error, Database::$REQ_ARRAY, array_slice($args, $offset));
    }

    /** This is a transactional version of querySingleNT.
     *
     * @param string $statement a non-empty string forming the prepared function: use ? for variables bound to this statement
     * @param function $callback_error An *optional* argument with a callback function in case error trapping is required.
     * If the second argument is a callable, then this argument is handled as an error callback. If it is any other type (including null), then it is passed as a binding argument.
     * @param anytype $argument... A variable argument list of each binded argument
     * @return array A single object as a result of this statement
     */
    public function querySingle($statement) {
        return $this->querySingleI(func_get_args(), true);
    }

    /** This is a non-transactional version of querySingle.
     *
     * @param string $statement a non-empty string forming the prepared function: use ? for variables bound to this statement
     * @param function $callback_error An *optional* argument with a callback function in case error trapping is required.
     * If the second argument is a callable, then this argument is handled as an error callback. If it is any other type (including null), then it is passed as a binding argument.
     * @param anytype $argument... A variable argument list of each binded argument
     * @return array A single object as a result of this statement
     */
    public function querySingleNT($statement) {
        return $this->querySingleI(func_get_args(), false);
    }

    private function querySingleI($args, $transactional) {
        $statement = $args[0];
        $offset = 1;
        $callback_error = $this->findErrorCallback($args, $offset);
        return $this->queryImpl($statement, $transactional, null, $callback_error, Database::$REQ_OBJECT, array_slice($args, $offset));
    }

    private function findErrorCallback($arguments, &$offset) {
        if ($arguments && count($arguments) > $offset && is_callable($arguments[$offset])) {
            $func = $arguments[$offset];
            $offset++;
            return $func;
        }
        return null;
    }

    private function queryImpl($statement, $isTransactional, $callback_fetch, $callback_error, $requestType, $variables) {
        $init_time = microtime();
        if (is_null($statement) || !is_string($statement) || empty($statement))
            return $this->errorFound($callback_error, $isTransactional, "First parameter of query should be a non-empty string; found " . gettype($statement), $statement, $init_time);
        if (!is_callable($callback_fetch) && !is_null($callback_fetch))
            return $this->errorFound($callback_error, $isTransactional, "Second parameter of query should be a closure, or null; found " . gettype($callback_fetch), $statement, $init_time);

        /* Start transaction, if required */
        if ($isTransactional && !$this->dbh->beginTransaction())
            return $this->errorFound($callback_error, $isTransactional, "Unable to initialize transaction", $statement, $init_time);

        /* flatten parameter array */
        $flatten = array();
        $it = new RecursiveIteratorIterator(new RecursiveArrayIterator($variables));
        foreach ($it as $v) {
            $flatten[] = $v;
        }
        $variables = $flatten;
        $variable_size = count($variables);
        $variable_types = array($variable_size);

        /* Construct actual statement */
        $statement_parts = explode("?", $statement);
        if ($variable_size != (count($statement_parts) - 1)) {  // Do not take into account first part
            Database::dbg("Parameter size and counted parameters do not match", $statement, $init_time, Debug::CRITICAL);
            die();
        }
        // Type safe input parameters
        $warning_parts = "";
        for ($i = 0; $i < $variable_size; $i++) {
            $entry = $statement_parts[$i + 1];
            $first = substr($entry, 0, 1);
            $value = $variables[$i];
            if ($first === "d") {   // Decimal
                $statement_parts[$i + 1] = substr($entry, 1);
                $value = intval($value);
                $type = PDO::PARAM_INT;
            } else if ($first === "b") {    // Boolean
                $statement_parts[$i + 1] = substr($entry, 1);
                $value = ($value == true);
                $type = PDO::PARAM_BOOL;
            } else if ($first === "f") {    // Floating point
                $statement_parts[$i + 1] = substr($entry, 1);
                $value = floatval($value);
                $type = PDO::PARAM_STR;
            } else if ($first === "s") {    // String
                $statement_parts[$i + 1] = substr($entry, 1);
                $type = PDO::PARAM_STR;
            } else {    // Auto-guess
                $warning_parts .= ", ";
                if (is_int($value)) {
                    $type = PDO::PARAM_INT;
                    $warning_parts .="int_" . $i . "=" . $value;
                } else if (is_bool($value)) {
                    $type = PDO::PARAM_BOOL;
                    $warning_parts .="bool_" . $i . "=" . $value;
                } else {
                    $type = PDO::PARAM_STR;
                    $warning_parts .="string_" . $i . "=\"" . $value . "\"";
                }
            }
            $variables[$i] = $value;
            $variable_types[$i] = $type;
        }
        if (strlen($warning_parts) > 0) {
            $warning_parts = substr($warning_parts, 1);
            Database::dbg("Warning: parts [ $warning_parts ] of query '$statement' have undefined type.", $statement, $init_time, Debug::ERROR);
        }
        $statement = implode("?", $statement_parts);

        /* Prepare statement */
        $stm = $this->dbh->prepare($statement);
        if (!$stm)
            return $this->errorFound($callback_error, $isTransactional, "Unable to prepare statement", $statement, $init_time);

        /* Bind values - with type safety and '?' notation  */
        for ($i = 0; $i < $variable_size; $i++) {
            if (!$stm->bindValue($i + 1, $variables[$i], $variable_types[$i]))
                $this->errorFound($callback_error, $isTransactional, "Unable to bind boolean parameter '$variables[$i]' with type $variable_types[$i] at location #$i", $statement, $init_time, false);
        }

        /* Execute statement */
        if (!$stm->execute())
            return $this->errorFound($callback_error, $isTransactional, "Unable to execute statement", $statement, $init_time);

        /* fetch results */
        $result = null;
        if ($requestType == Database::$REQ_OBJECT) {
            $result = $stm->fetch(PDO::FETCH_OBJ);
            if (!is_object($result))
                return $this->errorFound($callback_error, $isTransactional, "Unable to fetch single result as object", $statement, $init_time);
        } else if ($requestType == Database::$REQ_ARRAY) {
            $result = $stm->fetchAll(PDO::FETCH_OBJ);
            if (!is_array($result))
                return $this->errorFound($callback_error, $isTransactional, "Unable to fetch all results as objects", $statement, $init_time);
        } else if ($requestType == Database::$REQ_LASTID) {
            $result = new DBResult($this->dbh->lastInsertId(), $stm->rowCount());
        } else if ($requestType == Database::$REQ_FUNCTION) {
            if ($callback_fetch)
                while (TRUE)
                    if (!($res = $stm->fetch(PDO::FETCH_OBJ)) || $callback_fetch($res))
                        break;
        }
        /* Close transaction, if required */
        if ($isTransactional)
            $this->dbh->commit();
        Database::dbg("Succesfully performed query", $statement, $init_time, Debug::INFO);
        return $result;
    }

    private function errorFound($callback_error, $isTransactional, $error_msg, $statement, $init_time, $close_transaction = true) {
        if ($callback_error && is_callable($callback_error))
            $callback_error($error_msg);
        if ($close_transaction && $isTransactional && $this->dbh->inTransaction())
            $this->dbh->rollBack();
        Database::dbg("Error: " . $error_msg, $statement, $init_time);
        return null;
    }

    /**
     * Private function to call master Debug object
     */
    private static function dbg($message, $statement, $init_time, $level = Debug::ERROR) {
        Debug::message($message . " [Statement='$statement' Elapsed='" . (microtime() - $init_time) . "]", $level);
    }

}
