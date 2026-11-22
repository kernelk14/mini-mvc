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
        Compat::detectBaseUrl();
        Compat::$booted = true;
    }

    static function detectBaseUrl()
    {
        $baseUrl = '';
        $app = mini_config('app');
        if (isset($app['base_url']) && $app['base_url'] !== '') {
            $baseUrl = $app['base_url'];
        } else {
            $scriptName = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
            $scriptName = str_replace('\\', '/', $scriptName);
            $dir = dirname($scriptName);
            $dir = str_replace('\\', '/', $dir);
            if ($dir === '/' || $dir === '.' || $dir === '\\') {
                $dir = '';
            }
            $baseUrl = $dir;
        }
        $baseUrl = str_replace('\\', '/', (string)$baseUrl);
        if ($baseUrl !== '' && $baseUrl[0] !== '/') {
            $baseUrl = '/' . $baseUrl;
        }
        $baseUrl = rtrim($baseUrl, '/');
        $GLOBALS['MINI_BASE_URL'] = $baseUrl;
        return $baseUrl;
    }

    static function baseUrl()
    {
        return isset($GLOBALS['MINI_BASE_URL']) ? $GLOBALS['MINI_BASE_URL'] : '';
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
function get($path, $handler, $name = null) { Core_Router::add('GET', $path, $handler, $name); }
function post($path, $handler, $name = null) { Core_Router::add('POST', $path, $handler, $name); }
function put($path, $handler, $name = null) { Core_Router::add('PUT', $path, $handler, $name); }
function patch($path, $handler, $name = null) { Core_Router::add('PATCH', $path, $handler, $name); }
function delete($path, $handler, $name = null) { Core_Router::add('DELETE', $path, $handler, $name); }
function any($path, $handler, $name = null) { Core_Router::add('ANY', $path, $handler, $name); }

function mini_base_path()
{
    return isset($GLOBALS['MINI_BASE_PATH']) ? $GLOBALS['MINI_BASE_PATH'] : Compat::$basePath;
}

function mini_base_url()
{
    return isset($GLOBALS['MINI_BASE_URL']) ? $GLOBALS['MINI_BASE_URL'] : '';
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

function renderLayout($template, $data = null, $layout = null)
{
    if ($data === null) { $data = array(); }
    if ($layout === null) { $layout = 'layouts/main'; }
    return Core_View::make($template, $data, $layout);
}

function redirect($url, $code = null)
{
    response()->redirect($url, $code);
}

function back($fallback = '/')
{
    $ref = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $fallback;
    redirect($ref);
}

function url($path = '')
{
    $path = (string)$path;
    $base = mini_base_url();
    $base = rtrim(str_replace('\\', '/', (string)$base), '/');
    if ($path === '' || $path === '/') {
        return $base === '' ? '/' : $base . '/';
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    if (strncmp($path, '//', 2) === 0) {
        return $path;
    }
    if ($path[0] !== '/') {
        $path = '/' . $path;
    }
    // avoid double-prefix if already includes base
    if ($base !== '' && (strncmp($path, $base . '/', strlen($base) + 1) === 0 || $path === $base)) {
        return $path;
    }
    return $base . $path;
}

function route($name, $params = null)
{
    $r = Core_Router::findByName($name);
    if (!$r || !isset($r['path'])) { return url('/'); }
    $path = $r['path'];
    if (is_array($params) && count($params) > 0) {
        // replace {param} placeholders in order
        $i = 0;
        while (strpos($path, '{') !== false && $i < count($params)) {
            $path = preg_replace('/\{[^}]+\}/', $params[$i], $path, 1);
            $i++;
        }
    }
    return url($path);
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

function old($key, $default = null)
{
    $s = session();
    return $s ? $s->old($key, $default) : $default;
}

function errors($key = null)
{
    $s = session();
    if (!$s) { return $key === null ? array() : null; }
    if ($key === null) { return $s->errors(); }
    return $s->getError($key, null);
}

function with_input($data = null)
{
    $s = session();
    if ($s) { $s->setOld($data === null ? request()->all() : $data); }
}

function with_errors($errs)
{
    $s = session();
    if ($s) { $s->setErrors($errs); }
}

function redirect_back($fallback = '/')
{
    with_input();
    back($fallback);
}

function hash_password($password)
{
    if (function_exists('password_hash')) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    if (function_exists('crypt')) {
        $salt = '$2y$10$' . substr(str_replace('+', '.', base64_encode(sha1(uniqid(mt_rand(), true), true))), 0, 22);
        return crypt($password, $salt);
    }
    return sha1($password);
}

function verify_password($password, $hash)
{
    if (function_exists('password_verify')) {
        return password_verify($password, $hash);
    }
    if (function_exists('crypt')) {
        return crypt($password, $hash) === $hash;
    }
    return sha1($password) === $hash;
}

function auth_check()
{
    return session_has('user_id');
}

function auth_id()
{
    return session_get('user_id', null);
}

function auth_user($modelName)
{
    if (!$modelName) { $modelName = 'User'; }
    if (!auth_check()) { return null; }
    $m = model($modelName);
    if (!$m || !$m->ready()) { return null; }
    return $m->find(auth_id());
}

function logger($message, $level = 'info')
{
    $dir = mini_base_path() . '/storage/logs';
    if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
    $file = $dir . '/app.log';
    $line = '[' . date('Y-m-d H:i:s') . '] ' . strtoupper($level) . ': ' . $message . "\n";
    @file_put_contents($file, $line, FILE_APPEND);
}

function validate($data, $rules)
{
    $errors = array();
    foreach ($rules as $field => $ruleStr) {
        $value = (is_array($data) && array_key_exists($field, $data)) ? $data[$field] : null;
        $ruleList = is_array($ruleStr) ? $ruleStr : explode('|', $ruleStr);
        $i = 0;
        while ($i < count($ruleList)) {
            $rule = $ruleList[$i];
            $param = null;
            if (strpos($rule, ':') !== false) {
                $parts = explode(':', $rule, 2);
                $rule = $parts[0];
                $param = $parts[1];
            }
            $r = trim($rule);
            if ($r === 'required' && ($value === null || $value === '' || (is_array($value) && count($value) === 0))) {
                $errors[$field] = $field . ' is required';
            } elseif ($r === 'email' && $value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[$field] = $field . ' must be a valid email';
            } elseif ($r === 'min' && $value !== null && $value !== '' && strlen((string)$value) < (int)$param) {
                $errors[$field] = $field . ' must be at least ' . $param . ' characters';
            } elseif ($r === 'max' && $value !== null && $value !== '' && strlen((string)$value) > (int)$param) {
                $errors[$field] = $field . ' must be at most ' . $param . ' characters';
            } elseif ($r === 'numeric' && $value !== null && $value !== '' && !is_numeric($value)) {
                $errors[$field] = $field . ' must be numeric';
            } elseif ($r === 'confirmed') {
                $conf = (is_array($data) && array_key_exists($field . '_confirmation', $data)) ? $data[$field . '_confirmation'] : null;
                if ($value !== $conf) { $errors[$field] = $field . ' confirmation does not match'; }
            }
            if (isset($errors[$field])) { break; }
            $i++;
        }
    }
    return $errors; // empty array = valid
}

function escape($value)
{
    return Core_View::escape($value);
}
