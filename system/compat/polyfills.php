<?php
/**
 * Runtime polyfills/wrappers for newer features on older PHP.
 * Syntax features (namespaces, closures, short arrays, traits) cannot be polyfilled.
 * PHP 5.0+ compatible.
 */

if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle)
    {
        if ($needle === '') {
            return true;
        }
        return strpos($haystack, $needle) !== false;
    }
}

if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle)
    {
        if ($needle === '') {
            return true;
        }
        return strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle)
    {
        if ($needle === '') {
            return true;
        }
        $len = strlen($needle);
        if ($len === 0) {
            return true;
        }
        return substr($haystack, -$len) === $needle;
    }
}

if (!function_exists('array_key_first')) {
    function array_key_first($arr)
    {
        if (!is_array($arr) || count($arr) === 0) {
            return null;
        }
        reset($arr);
        return key($arr);
    }
}

if (!function_exists('array_key_last')) {
    function array_key_last($arr)
    {
        if (!is_array($arr) || count($arr) === 0) {
            return null;
        }
        end($arr);
        return key($arr);
    }
}

if (!function_exists('htmlspecialchars_decode_safe')) {
    function htmlspecialchars_decode_safe($str)
    {
        if (function_exists('htmlspecialchars_decode')) {
            return htmlspecialchars_decode($str, ENT_QUOTES);
        }
        $trans = array('&amp;' => '&', '&quot;' => '"', '&#039;' => "'", '&lt;' => '<', '&gt;' => '>');
        return strtr($str, $trans);
    }
}

if (!function_exists('mini_json_encode')) {
    function mini_json_encode($value)
    {
        if (function_exists('json_encode')) {
            return json_encode($value);
        }
        // Minimal fallback for simple arrays/scalars/objects
        if (is_null($value)) { return 'null'; }
        if ($value === true) { return 'true'; }
        if ($value === false) { return 'false'; }
        if (is_int($value) || is_float($value)) { return (string)$value; }
        if (is_string($value)) {
            $search = array("\\", "\"", "\n", "\r", "\t");
            $replace = array("\\\\", "\\\"", "\\n", "\\r", "\\t");
            return '"' . str_replace($search, $replace, $value) . '"';
        }
        if (is_array($value)) {
            $isAssoc = false;
            $keys = array_keys($value);
            $i = 0;
            while ($i < count($keys)) {
                if ($keys[$i] !== $i) { $isAssoc = true; break; }
                $i++;
            }
            $items = array();
            if ($isAssoc) {
                foreach ($value as $k => $v) {
                    $items[] = mini_json_encode((string)$k) . ':' . mini_json_encode($v);
                }
                return '{' . implode(',', $items) . '}';
            } else {
                foreach ($value as $v) {
                    $items[] = mini_json_encode($v);
                }
                return '[' . implode(',', $items) . ']';
            }
        }
        if (is_object($value)) {
            return mini_json_encode(get_object_vars($value));
        }
        return 'null';
    }
}

if (!function_exists('mini_json_decode')) {
    function mini_json_decode($json, $assoc)
    {
        if (function_exists('json_decode')) {
            if ($assoc) { return json_decode($json, true); }
            return json_decode($json);
        }
        // Very limited fallback: not a full JSON parser; returns null if unavailable.
        return null;
    }
}
