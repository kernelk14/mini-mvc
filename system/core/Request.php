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

    function isGet()
    {
        return $this->method() === 'GET';
    }

    function has($key)
    {
        return array_key_exists($key, $_POST) || array_key_exists($key, $_GET);
    }

    function only($keys)
    {
        $out = array();
        if (!is_array($keys)) { return $out; }
        $i = 0;
        while ($i < count($keys)) {
            $k = $keys[$i];
            if ($this->has($k)) { $out[$k] = $this->input($k, null); }
            $i++;
        }
        return $out;
    }

    function except($keys)
    {
        $all = $this->all();
        if (!is_array($keys)) { return $all; }
        $i = 0;
        while ($i < count($keys)) {
            $k = $keys[$i];
            if (array_key_exists($k, $all)) { unset($all[$k]); }
            $i++;
        }
        return $all;
    }

    function cookie($key, $default = null)
    {
        return array_key_exists($key, $_COOKIE) ? $_COOKIE[$key] : $default;
    }

    function file($key)
    {
        return (isset($_FILES) && array_key_exists($key, $_FILES)) ? $_FILES[$key] : null;
    }

    function hasFile($key)
    {
        if (!isset($_FILES) || !array_key_exists($key, $_FILES)) { return false; }
        $f = $_FILES[$key];
        return isset($f['error']) && $f['error'] !== UPLOAD_ERR_NO_FILE;
    }

    function ip()
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) { return $_SERVER['HTTP_X_FORWARDED_FOR']; }
        if (!empty($_SERVER['REMOTE_ADDR'])) { return $_SERVER['REMOTE_ADDR']; }
        return '';
    }

    function header($key, $default = null)
    {
        $name = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        if (isset($_SERVER[$name])) { return $_SERVER[$name]; }
        if ($key === 'Content-Type' && isset($_SERVER['CONTENT_TYPE'])) { return $_SERVER['CONTENT_TYPE']; }
        if ($key === 'Content-Length' && isset($_SERVER['CONTENT_LENGTH'])) { return $_SERVER['CONTENT_LENGTH']; }
        return $default;
    }
}
