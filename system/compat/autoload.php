<?php
/**
 * Unified class autoloading with smart fallback for PHP 5.0+.
 */

function mini_autoload_register($basePath)
{
    $GLOBALS['MINI_BASE_PATH'] = $basePath;
    $GLOBALS['MINI_AUTOLOAD_MAP'] = array(
        'Core_'  => $basePath . '/system/core/',
        'Compat_'=> $basePath . '/system/compat/',
    );

    if (function_exists('spl_autoload_register')) {
        spl_autoload_register('mini_autoload');
    } else {
        // PHP < 5.1.2 fallback
        if (!function_exists('__autoload')) {
            eval('function __autoload($class){ mini_autoload($class); }');
        }
    }
}

function mini_autoload($class)
{
    $base = isset($GLOBALS['MINI_BASE_PATH']) ? $GLOBALS['MINI_BASE_PATH'] : dirname(dirname(dirname(__FILE__)));

    // Direct known prefixes
    $prefixes = isset($GLOBALS['MINI_AUTOLOAD_MAP']) ? $GLOBALS['MINI_AUTOLOAD_MAP'] : array();
    foreach ($prefixes as $prefix => $dir) {
        if (strncmp($class, $prefix, strlen($prefix)) === 0) {
            $name = substr($class, strlen($prefix));
            $file = $dir . $name . '.php';
            if (is_file($file)) { require_once $file; return true; }
        }
    }

    // App controllers/models (e.g. HomeController, UserModel)
    $paths = array(
        $base . '/app/controllers/' . $class . '.php',
        $base . '/app/models/' . $class . '.php',
        $base . '/system/core/' . $class . '.php',
        $base . '/system/compat/' . $class . '.php',
    );
    $i = 0;
    while ($i < count($paths)) {
        if (is_file($paths[$i])) { require_once $paths[$i]; return true; }
        $i++;
    }

    // Underscore-to-path mapping
    $rel = str_replace('_', '/', $class) . '.php';
    $paths2 = array(
        $base . '/' . $rel,
        $base . '/system/core/' . basename($rel),
        $base . '/app/controllers/' . basename($rel),
        $base . '/app/models/' . basename($rel),
    );
    $i = 0;
    while ($i < count($paths2)) {
        if (is_file($paths2[$i])) { require_once $paths2[$i]; return true; }
        $i++;
    }

    return false;
}
