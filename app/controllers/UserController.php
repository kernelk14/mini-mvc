<?php
class UserController extends Core_Controller
{
    function index()
    {
        $users = array();
        $dbError = null;
        $model = new UserModel();
        if ($model->db && $model->db->isConnected()) {
            $users = $model->all(100);
            $dbError = $model->db->error();
        } else {
            $dbError = $model->db ? $model->db->error() : 'Database not configured';
        }
        $debug = function_exists('mini_debug') ? mini_debug() : false;
        $this->view('user/index', array(
            'title' => 'Users',
            'users' => $users,
            'dbError' => $debug ? $dbError : ($dbError ? 'Database unavailable' : null),
            'csrf' => function_exists('mini_csrf_token') ? mini_csrf_token() : '',
        ));
    }

    function show()
    {
        $id = $this->request->get('id', null);
        $user = null;
        $dbError = null;
        $model = new UserModel();
        if ($model->db && $model->db->isConnected() && $id !== null) {
            $user = $model->find($id);
            $dbError = $model->db->error();
        } else {
            $dbError = $model->db ? $model->db->error() : 'Database not configured';
        }
        $debug = function_exists('mini_debug') ? mini_debug() : false;
        $this->view('user/show', array(
            'title' => 'User',
            'user' => $user,
            'id' => $id,
            'dbError' => $debug ? $dbError : ($dbError ? 'Database unavailable' : null),
        ));
    }

    function save()
    {
        // Example POST handler (expects "name" field + CSRF token)
        $name = $this->request->post('name', '');
        $saved = false;
        $dbError = null;
        $error = null;
        $debug = function_exists('mini_debug') ? mini_debug() : false;

        if (!$this->request->isPost()) {
            $this->response->status(405);
            $this->response->json(array('saved' => false, 'error' => 'Method not allowed'));
            return;
        }

        $token = $this->request->post('_csrf', '');
        if (function_exists('mini_csrf_check') && !mini_csrf_check($token)) {
            $this->response->status(403);
            $this->response->json(array('saved' => false, 'error' => 'Invalid CSRF token'));
            return;
        }

        if (is_array($name)) { $name = ''; }
        $name = trim((string)$name);
        if ($name === '') {
            $this->response->status(400);
            $this->response->json(array('saved' => false, 'error' => 'Name is required'));
            return;
        }
        if (strlen($name) > 255) { $name = substr($name, 0, 255); }

        $model = new UserModel();
        if ($model->db && $model->db->isConnected()) {
            $res = $model->query('INSERT INTO users (name) VALUES (?)', array($name));
            $saved = ($res !== false);
            $dbError = $model->db->error();
        } else {
            $dbError = $model->db ? $model->db->error() : 'Database not configured';
        }

        if (!$saved) {
            $error = $debug ? $dbError : 'Unable to save';
        }

        $this->response->json(array(
            'saved' => $saved,
            'name' => $name,
            'error' => $error,
        ));
    }
}
