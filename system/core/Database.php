<?php
/**
 * Minimal DB wrapper with PDO/mysqli/mysql fallback and MySQL/SQLite support.
 * PHP 5.0+ compatible (driver availability depends on PHP build).
 */
class Core_Database
{
    var $driver = 'none';   // pdo, mysqli, mysql, none
    var $pdoDriver = null;  // mysql, sqlite
    var $conn = null;       // resource/handle
    var $pdo = null;        // PDO object
    var $config = array();
    var $lastError = '';
    var $connected = false;

    function __construct($config = null)
    {
        $this->Core_Database($config);
    }

    function Core_Database($config)
    {
        $this->config = is_array($config) ? $config : array();
        $this->connect($this->config);
    }

    function connect($config)
    {
        $this->config = is_array($config) ? $config : array();
        $driver = isset($this->config['driver']) ? $this->config['driver'] : 'auto';
        $dbType = isset($this->config['type']) ? $this->config['type'] : 'mysql'; // mysql or sqlite

        if ($driver === 'auto') {
            $driver = $this->selectDriver($dbType);
        }
        $this->driver = $driver;

        if ($driver === 'pdo') {
            return $this->connectPdo($dbType);
        } elseif ($driver === 'mysqli') {
            return $this->connectMysqli();
        } elseif ($driver === 'mysql') {
            return $this->connectMysql();
        }

        $this->lastError = 'No supported database driver available';
        $this->connected = false;
        return false;
    }

    function selectDriver($dbType)
    {
        $ver = isset($GLOBALS['COMPAT_VERSION']) ? $GLOBALS['COMPAT_VERSION'] : null;
        if ($dbType === 'sqlite') {
            if ($ver && $ver->hasPdoSqlite) { return 'pdo'; }
            if (class_exists('PDO')) { return 'pdo'; }
            return 'none';
        }
        // mysql/mariadb
        if ($ver) {
            if ($ver->hasPdoMysql) { return 'pdo'; }
            if ($ver->hasMysqli) { return 'mysqli'; }
            if ($ver->hasMysql) { return 'mysql'; }
        } else {
            if (class_exists('PDO')) { return 'pdo'; }
            if (function_exists('mysqli_connect')) { return 'mysqli'; }
            if (function_exists('mysql_connect')) { return 'mysql'; }
        }
        return 'none';
    }

    function connectPdo($dbType)
    {
        $this->pdoDriver = $dbType;
        $charset = isset($this->config['charset']) ? $this->config['charset'] : 'utf8';
        try {
            if ($dbType === 'sqlite') {
                $path = isset($this->config['path']) ? $this->config['path'] : (mini_base_path() . '/storage/app/database.sqlite');
                $dsn = 'sqlite:' . $path;
                $this->pdo = new PDO($dsn);
            } else {
                $host = isset($this->config['host']) ? $this->config['host'] : 'localhost';
                $name = isset($this->config['name']) ? $this->config['name'] : '';
                $user = isset($this->config['user']) ? $this->config['user'] : '';
                $pass = isset($this->config['pass']) ? $this->config['pass'] : '';
                $port = isset($this->config['port']) ? $this->config['port'] : null;
                $dsn = 'mysql:host=' . $host . ';dbname=' . $name;
                if ($port) { $dsn .= ';port=' . $port; }
                $dsn .= ';charset=' . $charset;
                $this->pdo = new PDO($dsn, $user, $pass);
            }
            // set error mode if available
            if (defined('PDO::ATTR_ERRMODE') && defined('PDO::ERRMODE_EXCEPTION')) {
                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }
            $this->connected = true;
            return true;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            $this->connected = false;
            $this->pdo = null;
            return false;
        }
    }

    function connectMysqli()
    {
        $host = isset($this->config['host']) ? $this->config['host'] : 'localhost';
        $name = isset($this->config['name']) ? $this->config['name'] : '';
        $user = isset($this->config['user']) ? $this->config['user'] : '';
        $pass = isset($this->config['pass']) ? $this->config['pass'] : '';
        $port = isset($this->config['port']) ? (int)$this->config['port'] : null;

        if ($port) {
            $this->conn = @mysqli_connect($host, $user, $pass, $name, $port);
        } else {
            $this->conn = @mysqli_connect($host, $user, $pass, $name);
        }
        if (!$this->conn) {
            $this->lastError = function_exists('mysqli_connect_error') ? mysqli_connect_error() : 'mysqli connect failed';
            $this->connected = false;
            return false;
        }
        $charset = isset($this->config['charset']) ? $this->config['charset'] : 'utf8';
        if (function_exists('mysqli_set_charset')) {
            @mysqli_set_charset($this->conn, $charset);
        }
        $this->connected = true;
        return true;
    }

    function connectMysql()
    {
        $host = isset($this->config['host']) ? $this->config['host'] : 'localhost';
        $name = isset($this->config['name']) ? $this->config['name'] : '';
        $user = isset($this->config['user']) ? $this->config['user'] : '';
        $pass = isset($this->config['pass']) ? $this->config['pass'] : '';
        $port = isset($this->config['port']) ? $this->config['port'] : null;
        $hostport = $port ? ($host . ':' . $port) : $host;

        $this->conn = @mysql_connect($hostport, $user, $pass);
        if (!$this->conn) {
            $this->lastError = function_exists('mysql_error') ? mysql_error() : 'mysql connect failed';
            $this->connected = false;
            return false;
        }
        if (!@mysql_select_db($name, $this->conn)) {
            $this->lastError = function_exists('mysql_error') ? mysql_error($this->conn) : 'mysql select db failed';
            $this->connected = false;
            return false;
        }
        $charset = isset($this->config['charset']) ? $this->config['charset'] : 'utf8';
        if (function_exists('mysql_set_charset')) {
            @mysql_set_charset($charset, $this->conn);
        } else {
            @mysql_query("SET NAMES '" . $charset . "'", $this->conn);
        }
        $this->connected = true;
        return true;
    }

    function isConnected()
    {
        return $this->connected;
    }

    function query($sql, $params)
    {
        if ($params === null) { $params = array(); }
        $this->lastError = '';

        if ($this->driver === 'pdo' && $this->pdo) {
            try {
                if (is_array($params) && count($params) > 0) {
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute($params);
                    return $stmt;
                } else {
                    return $this->pdo->query($sql);
                }
            } catch (Exception $e) {
                $this->lastError = $e->getMessage();
                return false;
            }
        }

        // interpolate params safely for mysqli/mysql
        $finalSql = $this->interpolate($sql, $params);

        if ($this->driver === 'mysqli' && $this->conn) {
            $res = @mysqli_query($this->conn, $finalSql);
            if ($res === false) {
                $this->lastError = mysqli_error($this->conn);
            }
            return $res;
        }

        if ($this->driver === 'mysql' && $this->conn) {
            $res = @mysql_query($finalSql, $this->conn);
            if ($res === false) {
                $this->lastError = mysql_error($this->conn);
            }
            return $res;
        }

        $this->lastError = 'No active database connection';
        return false;
    }

    function interpolate($sql, $params)
    {
        if (!is_array($params) || count($params) === 0) {
            return $sql;
        }
        // Replace ? placeholders in order
        $parts = explode('?', $sql);
        $out = '';
        $i = 0;
        $max = count($parts);
        while ($i < $max) {
            $out .= $parts[$i];
            if ($i < $max - 1) {
                $val = array_shift($params);
                $out .= $this->quote($val);
            }
            $i++;
        }
        return $out;
    }

    function quote($value)
    {
        if ($value === null) { return 'NULL'; }
        if (is_int($value) || is_float($value)) { return (string)$value; }
        if (is_bool($value)) { return $value ? '1' : '0'; }
        return "'" . $this->escape((string)$value) . "'";
    }

    function fetch($result)
    {
        if (!$result) { return null; }
        if ($this->driver === 'pdo') {
            if (method_exists($result, 'fetch')) {
                $row = $result->fetch(PDO::FETCH_ASSOC);
                return $row ? $row : null;
            }
            return null;
        }
        if ($this->driver === 'mysqli') {
            $row = @mysqli_fetch_assoc($result);
            return $row ? $row : null;
        }
        if ($this->driver === 'mysql') {
            $row = @mysql_fetch_assoc($result);
            return $row ? $row : null;
        }
        return null;
    }

    function fetchAll($result)
    {
        $rows = array();
        if (!$result) { return $rows; }
        if ($this->driver === 'pdo') {
            if (method_exists($result, 'fetchAll')) {
                $all = $result->fetchAll(PDO::FETCH_ASSOC);
                return is_array($all) ? $all : $rows;
            }
            // iterate
            while ($row = $this->fetch($result)) { $rows[] = $row; }
            return $rows;
        }
        while (true) {
            $row = $this->fetch($result);
            if ($row === null) { break; }
            $rows[] = $row;
        }
        return $rows;
    }

    function escape($value)
    {
        if ($value === null) { return ''; }
        $str = (string)$value;
        if ($this->driver === 'pdo' && $this->pdo) {
            // PDO::quote adds surrounding quotes; strip them
            $q = $this->pdo->quote($str);
            if ($q !== false && strlen($q) >= 2) {
                return substr($q, 1, -1);
            }
            return addslashes($str);
        }
        if ($this->driver === 'mysqli' && $this->conn) {
            return mysqli_real_escape_string($this->conn, $str);
        }
        if ($this->driver === 'mysql' && $this->conn) {
            return mysql_real_escape_string($str, $this->conn);
        }
        if (function_exists('addslashes')) { return addslashes($str); }
        return str_replace("'", "''", $str);
    }

    function lastInsertId()
    {
        if ($this->driver === 'pdo' && $this->pdo) {
            return $this->pdo->lastInsertId();
        }
        if ($this->driver === 'mysqli' && $this->conn) {
            return mysqli_insert_id($this->conn);
        }
        if ($this->driver === 'mysql' && $this->conn) {
            return mysql_insert_id($this->conn);
        }
        return null;
    }

    function execute($sql, $params = null)
    {
        $res = $this->query($sql, $params);
        return $res !== false;
    }

    function error()
    {
        return $this->lastError;
    }

    function close()
    {
        if ($this->driver === 'pdo') {
            $this->pdo = null;
        } elseif ($this->driver === 'mysqli' && $this->conn) {
            @mysqli_close($this->conn);
            $this->conn = null;
        } elseif ($this->driver === 'mysql' && $this->conn) {
            @mysql_close($this->conn);
            $this->conn = null;
        }
        $this->connected = false;
    }
}
