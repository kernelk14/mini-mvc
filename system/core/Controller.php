<?php
/**
 * Base controller.
 */
class Core_Controller
{
    var $request;
    var $response;
    var $db;
    var $config;

    function __construct()
    {
        $this->Core_Controller();
    }

    function Core_Controller()
    {
        $this->request = new Core_Request();
        $this->response = new Core_Response();
        $this->config = array();
        $this->init();
    }

    function init()
    {
        // Hook for subclasses; DB loaded lazily via db()
    }

    function view($template, $data)
    {
        if ($data === null) { $data = array(); }
        Core_View::render($template, $data, false);
    }

    function render($template, $data)
    {
        if ($data === null) { $data = array(); }
        return Core_View::render($template, $data, true);
    }

    function db()
    {
        if ($this->db === null) {
            $cfg = mini_config('database');
            $this->db = new Core_Database($cfg);
        }
        return $this->db;
    }

    function redirect($url)
    {
        $this->response->redirect($url, 302);
    }
}
