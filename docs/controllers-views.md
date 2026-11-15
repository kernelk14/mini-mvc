# Controllers & Views

## Controllers
Create controllers in `app/controllers/` extending `Core_Controller`:
```php
<?php
class UserController extends Core_Controller
{
    function index()
    {
        $this->view('user/index', array('title' => 'Users'));
    }

    function show()
    {
        $id = $this->request->get('id', null);
        $this->view('user/show', array('id' => $id));
    }
}
```

### Base helpers
- `$this->request` — `Core_Request` (method, uri, path, get/post/input/all, isPost).
- `$this->response` — `Core_Response` (status, header, redirect, json).
- `$this->session` — `Core_Session` (get, set, has, remove/forget, pull, all, clear, regenerate, destroy).
- `$this->view($template, $data)` — render `app/views/$template.php`.
- `$this->render($template, $data)` — render to string.
- `$this->db()` — lazily get `Core_Database` from `config/database.php`.
- `$this->redirect($url)`.

## Sessions
Sessions are started automatically during `Compat::boot()` with secure cookie settings. Use `$this->session` in controllers:
```php
// login
$user = $model->findByCredentials($email, $password);
if ($user) {
    $this->session->regenerate(true);
    $this->session->set('user_id', $user['id']);
    $this->redirect('/dashboard');
}

// read/check
$userId = $this->session->get('user_id');
if (!$this->session->has('user_id')) { $this->redirect('/login'); }

// logout
$this->session->destroy();
$this->redirect('/login');
```

## Views
Plain PHP templates in `app/views/`. Escape all untrusted output with `Core_View::escape($value)` / `Core_View::e($value)` or `htmlspecialchars(...)`. View names are sanitized and confined to `app/views/`. No built-in layout engine; compose via includes/partials or rendered strings.

## JSON
```php
$this->response->json(array('ok' => true));
```

## CSRF for state-changing requests
Use `mini_csrf_field()` in forms and verify in controllers with `mini_csrf_check($this->request->post('_csrf', ''))`. See `app/controllers/UserController.php` for an example.
