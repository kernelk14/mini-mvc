<?php
/**
 * Session helper.
 * Session is started securely during Compat::boot().
 */
class Core_Session
{
    function __construct()
    {
        $this->Core_Session();
    }

    function Core_Session()
    {
        if (!isset($_SESSION) || !is_array($_SESSION)) {
            $_SESSION = array();
        }
    }

    function get($key, $default = null)
    {
        if (isset($_SESSION) && array_key_exists($key, $_SESSION)) {
            return $_SESSION[$key];
        }
        return $default;
    }

    function set($key, $value)
    {
        if (!isset($_SESSION) || !is_array($_SESSION)) {
            $_SESSION = array();
        }
        $_SESSION[$key] = $value;
        return $value;
    }

    function has($key)
    {
        return isset($_SESSION) && array_key_exists($key, $_SESSION);
    }

    function remove($key)
    {
        if (isset($_SESSION) && array_key_exists($key, $_SESSION)) {
            unset($_SESSION[$key]);
        }
    }

    function forget($key)
    {
        $this->remove($key);
    }

    function pull($key, $default = null)
    {
        $value = $this->get($key, $default);
        $this->remove($key);
        return $value;
    }

    function all()
    {
        return isset($_SESSION) && is_array($_SESSION) ? $_SESSION : array();
    }

    function clear()
    {
        $_SESSION = array();
    }

    function regenerate($deleteOld = true)
    {
        if (function_exists('session_regenerate_id')) {
            @session_regenerate_id($deleteOld ? true : false);
        }
    }

    function destroy()
    {
        $_SESSION = array();

        if (function_exists('session_get_cookie_params') && function_exists('session_name')) {
            $params = session_get_cookie_params();
            $name = session_name();
            $path = isset($params['path']) ? $params['path'] : '/';
            $domain = isset($params['domain']) ? $params['domain'] : '';
            $secure = isset($params['secure']) ? $params['secure'] : false;
            $httponly = isset($params['httponly']) ? $params['httponly'] : false;

            if (function_exists('setcookie')) {
                if (defined('PHP_VERSION_ID') ? PHP_VERSION_ID >= 70300 : (class_exists('Compat_Version') ? Compat::$version->atLeast(7,3,0) : false)) {
                    @setcookie($name, '', time() - 42000, array(
                        'path' => $path,
                        'domain' => $domain,
                        'secure' => $secure,
                        'httponly' => $httponly,
                        'samesite' => isset($params['samesite']) ? $params['samesite'] : 'Lax',
                    ));
                } else {
                    @setcookie($name, '', time() - 42000, $path, $domain, $secure, $httponly);
                }
            }
        }

        if (function_exists('session_destroy')) {
            @session_destroy();
        }
        $_SESSION = array();
    }

    function id()
    {
        if (function_exists('session_id')) {
            return session_id();
        }
        return '';
    }

    function flash($key, $value)
    {
        $this->set($key, $value);
    }
}
