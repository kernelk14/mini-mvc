<?php
/**
 * Simple loader/helper utilities.
 */
class Core_Loader
{
    function config($file)
    {
        return mini_config($file);
    }

    function model($name)
    {
        $class = $name;
        if (!class_exists($class)) {
            $path = mini_base_path() . '/app/models/' . $name . '.php';
            if (is_file($path)) { require_once $path; }
        }
        if (class_exists($class)) {
            return new $class();
        }
        return null;
    }
}
