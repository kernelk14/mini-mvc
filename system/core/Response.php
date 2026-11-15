<?php
/**
 * Minimal response helper.
 */
class Core_Response
{
    function status($code)
    {
        if (function_exists('http_response_code')) {
            http_response_code($code);
        } else {
            $msg = 'OK';
            if ($code == 404) { $msg = 'Not Found'; }
            else if ($code == 500) { $msg = 'Internal Server Error'; }
            else if ($code == 302) { $msg = 'Found'; }
            else if ($code == 403) { $msg = 'Forbidden'; }
            header('HTTP/1.1 ' . (int)$code . ' ' . $msg);
        }
    }

    function header($name, $value)
    {
        header($name . ': ' . $value);
    }

    function redirect($url, $code = null)
    {
        if ($code === null) { $code = 302; }
        $url = (string)$url;
        // Prevent header injection and open redirects: allow relative URLs only by default
        if (preg_match('/[\r\n]/', $url)) {
            $url = '/';
        }
        if (preg_match('#^https?://#i', $url) || strpos($url, '//') === 0) {
            $url = '/';
        }
        $this->status($code);
        $this->header('Location', $url);
        exit;
    }

    function json($data, $status = null)
    {
        if ($status !== null) {
            $this->status((int)$status);
        }
        $this->header('Content-Type', 'application/json; charset=utf-8');
        if (function_exists('mini_json_encode')) {
            echo mini_json_encode($data);
        } elseif (function_exists('json_encode')) {
            echo json_encode($data);
        } else {
            echo 'null';
        }
    }
}
