<?php
/**
 * PHP version scanner and capability flags.
 * PHP 5.0+ compatible.
 */
class Compat_Version
{
    var $phpVersion;
    var $phpId;

    var $hasSplAutoload;
    var $hasPdo;
    var $hasMysqli;
    var $hasMysql;
    var $hasPdoMysql;
    var $hasPdoSqlite;
    var $hasJson;
    var $hasMbstring;
    var $hasException;

    function __construct()
    {
        $this->Compat_Version();
    }

    function Compat_Version()
    {
        $this->scan();
    }

    function scan()
    {
        $this->phpVersion = PHP_VERSION;
        $this->phpId = $this->normalize(PHP_VERSION);

        $this->hasSplAutoload = function_exists('spl_autoload_register');
        $this->hasPdo = class_exists('PDO');
        $this->hasMysqli = function_exists('mysqli_connect');
        $this->hasMysql = function_exists('mysql_connect');
        $this->hasPdoMysql = $this->hasPdoExtension('mysql');
        $this->hasPdoSqlite = $this->hasPdoExtension('sqlite');
        $this->hasJson = function_exists('json_encode') && function_exists('json_decode');
        $this->hasMbstring = function_exists('mb_strlen');
        $this->hasException = class_exists('Exception');
    }

    function hasPdoExtension($name)
    {
        if (!$this->hasPdo) {
            return false;
        }
        if (method_exists('PDO', 'getAvailableDrivers')) {
            $drivers = PDO::getAvailableDrivers();
            $i = 0;
            while ($i < count($drivers)) {
                if ($drivers[$i] === $name) {
                    return true;
                }
                $i++;
            }
        }
        return false;
    }

    function normalize($version)
    {
        $parts = explode('.', $version);
        $major = isset($parts[0]) ? (int)$parts[0] : 0;
        $minor = isset($parts[1]) ? (int)$parts[1] : 0;
        $patchStr = isset($parts[2]) ? $parts[2] : '0';
        $patch = (int)$patchStr;
        return ($major * 10000) + ($minor * 100) + $patch;
    }

    function atLeast($major, $minor, $patch)
    {
        $need = ($major * 10000) + ($minor * 100) + $patch;
        return $this->phpId >= $need;
    }

    function preferredDbDriver()
    {
        if ($this->hasPdoMysql || $this->hasPdoSqlite) {
            return 'pdo';
        }
        if ($this->hasMysqli) {
            return 'mysqli';
        }
        if ($this->hasMysql) {
            return 'mysql';
        }
        return 'none';
    }
}
