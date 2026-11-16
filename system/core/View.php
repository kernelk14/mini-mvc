<?php
/**
 * Minimal view renderer.
 */
class Core_View
{
    static function render($template, $data, $return)
    {
        $base = mini_base_path();
        $template = str_replace('\\', '/', (string)$template);
        $template = preg_replace('/[^A-Za-z0-9_\/\-]/', '', $template);
        if ($template === '' || strpos($template, '..') !== false) {
            echo 'View not found';
            return false;
        }

        $file = $base . '/app/views/' . $template . '.php';
        $viewsBase = realpath($base . '/app/views');
        $realFile = realpath($file);
        if ($realFile === false || !is_file($realFile)) {
            echo 'View not found';
            return false;
        }
        if ($viewsBase !== false) {
            $viewsBaseNorm = str_replace('\\', '/', $viewsBase);
            $realNorm = str_replace('\\', '/', $realFile);
            if (strpos($realNorm, $viewsBaseNorm) !== 0) {
                echo 'View not found';
                return false;
            }
        }

        if (!is_array($data)) {
            $data = array();
        }
        if (!isset($data['csrf_token']) && function_exists('csrf_token')) {
            $data['csrf_token'] = csrf_token();
        }
        if (!isset($data['csrf_field']) && function_exists('csrf_field')) {
            $data['csrf_field'] = csrf_field();
        }
        if (!isset($data['base_url'])) {
            $data['base_url'] = function_exists('mini_base_url') ? mini_base_url() : (isset($GLOBALS['MINI_BASE_URL']) ? $GLOBALS['MINI_BASE_URL'] : '');
        }

        extract($data);

        if ($return) {
            ob_start();
            include $realFile;
            $content = ob_get_contents();
            ob_end_clean();
            return $content;
        }

        include $realFile;
        return true;
    }

    static function escape($value)
    {
        if ($value === null) { $value = ''; }
        $value = (string)$value;
        $charset = 'UTF-8';
        if (function_exists('htmlspecialchars')) {
            if (defined('ENT_QUOTES') && defined('ENT_SUBSTITUTE')) {
                return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, $charset);
            }
            return htmlspecialchars($value, ENT_QUOTES, $charset);
        }
        return str_replace(array('&', '"', "'", '<', '>'), array('&amp;', '&quot;', '&#039;', '&lt;', '&gt;'), $value);
    }
}
