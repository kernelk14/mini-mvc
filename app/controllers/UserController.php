<?php
class UserController extends Core_Controller
{
    function index()
    {
        $m = model('User');
        $users = ($m && $m->db && $m->db->isConnected()) ? $m->all(100) : array();
        $err = ($m && $m->db) ? $m->db->error() : 'Database not configured';
        return view('user/index', array(
            'title' => 'Users',
            'users' => $users,
            'dbError' => mini_debug() ? $err : ($err ? 'Database unavailable' : null),
        ));
    }

    function show()
    {
        $id = input('id');
        $m = model('User');
        $user = ($m && $m->db && $m->db->isConnected() && $id !== null) ? $m->find($id) : null;
        $err = ($m && $m->db) ? $m->db->error() : 'Database not configured';
        return view('user/show', array(
            'title' => 'User',
            'user' => $user,
            'id' => $id,
            'dbError' => mini_debug() ? $err : ($err ? 'Database unavailable' : null),
        ));
    }

    function save()
    {
        if (!is_post()) { return json(array('saved' => false, 'error' => 'Method not allowed'), 405); }
        if (!csrf_valid()) { return json(array('saved' => false, 'error' => 'Invalid CSRF token'), 403); }

        $name = trim((string)input('name', ''));
        if ($name === '') { return json(array('saved' => false, 'error' => 'Name is required'), 400); }
        if (strlen($name) > 255) { $name = substr($name, 0, 255); }

        $m = model('User');
        $id = ($m && $m->db && $m->db->isConnected()) ? $m->create(array('name' => $name)) : false;
        $err = ($m && $m->db) ? $m->db->error() : 'Database not configured';
        return json(array(
            'saved' => $id ? true : false,
            'id' => $id ? $id : null,
            'name' => $name,
            'error' => $id ? null : (mini_debug() ? $err : 'Unable to save'),
        ));
    }
}
