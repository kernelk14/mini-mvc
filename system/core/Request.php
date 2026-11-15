<?php
/**
 * Minimal request wrapper.
 */
class Core_Request
{
    function method()
    {
        return isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
    }

    function uri()
    {
        return isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
    }

    function path()
    {
        $uri = $this->uri();
        if (function_exists('parse_url')) {
            $p = @parse_url($uri, PHP_URL_PATH);
            if ($p !== null && $p !== false) {
                return $p;
            }
        }
        $qpos = strpos($uri, '?');
        if ($qpos !== false) {
            return substr($uri, 0, $qpos);
        }
        return $uri;
    }

    function get($key, $default = null)
    {
        return array_key_exists($key, $_GET) ? $_GET[$key] : $default;
    }

    function post($key, $default = null)
    {
        return array_key_exists($key, $_POST) ? $_POST[$key] : $default;
    }

    function input($key, $default = null)
    {
        if (array_key_exists($key, $_POST)) { return $_POST[$key]; }
        if (array_key_exists($key, $_GET)) { return $_GET[$key]; }
        return $default;
    }

    function all()
    {
        $data = array();
        if (is_array($_GET)) {
            foreach ($_GET as $k => $v) { $data[$k] = $v; }
        }
        if (is_array($_POST)) {
            foreach ($_POST as $k => $v) { $data[$k] = $v; }
        }
        return $data;
    }

    function isPost()
    {
        return $this->method() === 'POST';
    }
}
