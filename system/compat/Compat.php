<?php
/**
 * Compatibility bootstrap, route helpers, and global shims.
 * PHP 5.0+ compatible.
 */

require_once dirname(__FILE__) . '/Version.php';
require_once dirname(__FILE__) . '/polyfills.php';
require_once dirname(__FILE__) . '/autoload.php';

class Compat
{
    static $version;
    static $basePath;
    static $booted = false;

    static function boot($basePath)
    {
        if (Compat::$booted) {
            return;
        }
        Compat::$basePath = $basePath;
        Compat::$version = new Compat_Version();
        $GLOBALS['COMPAT_VERSION'] = Compat::$version;

        mini_autoload_register($basePath);

        if (!defined('DIRECTORY_SEPARATOR')) {
            define('DIRECTORY_SEPARATOR', '/');
        }

        $app = mini_config('app');
        $debug = isset($app['debug']) ? $app['debug'] : false;
        $GLOBALS['MINI_DEBUG'] = $debug ? true : false;

        if ($debug) {
            @error_reporting(E_ALL);
            @ini_set('display_errors', '1');
        } else {
            @error_reporting(0);
            @ini_set('display_errors', '0');
        }

        @ini_set('expose_php', '0');
        if (function_exists('header_remove')) {
            @header_remove('X-Powered-By');
        }

        Compat::startSecureSession();
        Compat::$booted = true;
    }

    static function startSecureSession()
    {
        if (defined('PHP_SESSION_ACTIVE')) {
            if (session_status() === PHP_SESSION_ACTIVE) { return; }
        } elseif (isset($_SESSION)) {
            return;
        }
        if (headers_sent()) { return; }

        $params = array();
        if (function_exists('session_get_cookie_params')) {
            $params = session_get_cookie_params();
        }
        $cookie = array();
        $cookie['lifetime'] = isset($params['lifetime']) ? $params['lifetime'] : 0;
        $cookie['path'] = isset($params['path']) ? $params['path'] : '/';
        $cookie['domain'] = isset($params['domain']) ? $params['domain'] : '';
        $cookie['secure'] = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        $cookie['httponly'] = true;

        if (function_exists('session_set_cookie_params')) {
            if (Compat::$version && Compat::$version->atLeast(7, 3, 0)) {
                @session_set_cookie_params(array(
                    'lifetime' => $cookie['lifetime'],
                    'path' => $cookie['path'],
                    'domain' => $cookie['domain'],
                    'secure' => $cookie['secure'],
                    'httponly' => $cookie['httponly'],
                    'samesite' => 'Lax',
                ));
            } else {
                $path = $cookie['path'];
                if ($path === '') { $path = '/'; }
                @session_set_cookie_params($cookie['lifetime'], $path . '; samesite=Lax', $cookie['domain'], $cookie['secure'], $cookie['httponly']);
            }
        }

        @session_start();
    }

    static function version()
    {
        return Compat::$version;
    }
}

/* Route helper functions (available after compat is loaded) */
function get($path, $handler) { Core_Router::add('GET', $path, $handler); }
function post($path, $handler) { Core_Router::add('POST', $path, $handler); }
function put($path, $handler) { Core_Router::add('PUT', $path, $handler); }
function patch($path, $handler) { Core_Router::add('PATCH', $path, $handler); }
function delete($path, $handler) { Core_Router::add('DELETE', $path, $handler); }
function any($path, $handler) { Core_Router::add('ANY', $path, $handler); }

function mini_base_path()
{
    return isset($GLOBALS['MINI_BASE_PATH']) ? $GLOBALS['MINI_BASE_PATH'] : Compat::$basePath;
}

function mini_config($file)
{
    if (!preg_match('/^[A-Za-z0-9_-]+$/', $file)) {
        return array();
    }
    $path = mini_base_path() . '/config/' . $file . '.php';
    if (is_file($path)) {
        return require $path;
    }
    return array();
}

function mini_debug()
{
    return !empty($GLOBALS['MINI_DEBUG']);
}

function mini_csrf_token()
{
    if (!isset($_SESSION)) { return ''; }
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = mini_random_token();
    }
    return $_SESSION['_csrf_token'];
}

function mini_csrf_field()
{
    $token = mini_csrf_token();
    return '<input type="hidden" name="_csrf" value="' . htmlspecialchars($token, ENT_QUOTES) . '">';
}

function mini_csrf_check($token)
{
    if (!isset($_SESSION) || empty($_SESSION['_csrf_token'])) {
        return false;
    }
    if ($token === null || $token === false) {
        return false;
    }
    $known = (string)$_SESSION['_csrf_token'];
    $given = (string)$token;
    if ($known === '' || $given === '') { return false; }
    if (function_exists('hash_equals')) {
        return hash_equals($known, $given);
    }
    if (strlen($known) !== strlen($given)) { return false; }
    $res = 0;
    $i = 0;
    $len = strlen($known);
    while ($i < $len) {
        $res |= ord($known[$i]) ^ ord($given[$i]);
        $i++;
    }
    return $res === 0;
}

function mini_random_token()
{
    if (function_exists('random_bytes')) {
        return bin2hex(random_bytes(32));
    }
    if (function_exists('openssl_random_pseudo_bytes')) {
        $bytes = openssl_random_pseudo_bytes(32);
        if ($bytes !== false) { return bin2hex($bytes); }
    }
    $data = uniqid(mt_rand(), true);
    $data .= isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    $data .= microtime();
    if (function_exists('hash')) {
        return hash('sha256', $data);
    }
    return md5($data);
}

/* App-facing helpers (short API, PHP 5.0-safe) */
function request()
{
    if (!isset($GLOBALS['MINI_REQUEST'])) {
        $GLOBALS['MINI_REQUEST'] = new Core_Request();
    }
    return $GLOBALS['MINI_REQUEST'];
}

function response()
{
    if (!isset($GLOBALS['MINI_RESPONSE'])) {
        $GLOBALS['MINI_RESPONSE'] = new Core_Response();
    }
    return $GLOBALS['MINI_RESPONSE'];
}

function session()
{
    if (!isset($GLOBALS['MINI_SESSION'])) {
        $GLOBALS['MINI_SESSION'] = new Core_Session();
    }
    return $GLOBALS['MINI_SESSION'];
}

function config($file, $default = null)
{
    $cfg = mini_config($file);
    if ($default === null) { return $cfg; }
    return is_array($cfg) && count($cfg) ? $cfg : $default;
}

function input($key, $default = null)
{
    return request()->input($key, $default);
}

function is_post()
{
    return request()->isPost();
}

function is_get()
{
    return request()->method() === 'GET';
}

function view($template, $data = null)
{
    if ($data === null) { $data = array(); }
    return Core_View::render($template, $data, false);
}

function render($template, $data = null)
{
    if ($data === null) { $data = array(); }
    return Core_View::render($template, $data, true);
}

function redirect($url, $code = null)
{
    response()->redirect($url, $code);
}

function json($data, $status = 200)
{
    response()->status($status);
    response()->json($data);
}

function abort($code, $message = null)
{
    $code = (int)$code;
    if ($message === null) { $message = ''; }
    response()->status($code);
    if ($code === 404) {
        $app = new Core_App();
        $app->notFound($message);
    } else {
        $debug = function_exists('mini_debug') ? mini_debug() : false;
        $msg = ($debug && $message !== '') ? $message : 'Error';
        echo '<h1>' . htmlspecialchars((string)$code, ENT_QUOTES) . '</h1><p>' . htmlspecialchars($msg, ENT_QUOTES) . '</p>';
    }
    exit;
}

function model($name)
{
    $class = $name;
    if (substr($class, -5) !== 'Model') {
        $class .= 'Model';
    }
    if (!class_exists($class)) {
        $path = mini_base_path() . '/app/models/' . $class . '.php';
        if (is_file($path)) { require_once $path; }
    }
    if (class_exists($class)) {
        return new $class();
    }
    return null;
}

function db()
{
    if (!isset($GLOBALS['MINI_DB'])) {
        $GLOBALS['MINI_DB'] = new Core_Database(mini_config('database'));
    }
    return $GLOBALS['MINI_DB'];
}

function csrf_token()
{
    return mini_csrf_token();
}

function csrf_field()
{
    return mini_csrf_field();
}

function csrf_valid($token = null)
{
    if ($token === null) {
        $token = request()->post('_csrf', '');
    }
    return mini_csrf_check($token);
}

function session_set($key, $value)
{
    return session()->set($key, $value);
}

function session_get($key, $default = null)
{
    return session()->get($key, $default);
}

function session_has($key)
{
    return session()->has($key);
}

function session_remove($key)
{
    return session()->remove($key);
}

function session_flash($key, $value)
{
    return session()->flash($key, $value);
}

function session_pull($key, $default = null)
{
    return session()->pull($key, $default);
}

function flash($key, $default = null)
{
    return session()->getFlash($key, $default);
}

function session_regen($deleteOld = true)
{
    return session()->regenerate($deleteOld);
}

function session_destroy_safe()
{
    return session()->destroy();
}

function e($value)
{
    return Core_View::escape($value);
}
