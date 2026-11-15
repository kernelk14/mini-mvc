<?php
class HomeController extends Core_Controller
{
    function index()
    {
        $data = array(
            'title' => 'Home',
            'message' => 'Welcome to Mini MVC',
            'phpVersion' => PHP_VERSION,
        );
        $this->view('home/index', $data);
    }
}
