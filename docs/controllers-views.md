# Controllers & Views

## Controllers

Create controllers in `app/controllers/` extending `Core_Controller`:

```php
<?php
class UserController extends Core_Controller
{
    function index()
    {
        $users = model('User')->all(100);
        return view('user/index', array('title' => 'Users', 'users' => $users));
    }

    function show($id)
    {
        $user = model('User')->find($id);
        return view('user/show', array('user' => $user, 'id' => $id));
    }
}
```

Route `{param}` segments and segment-fallback params are passed to action arguments.

## Base helpers (controller)

- `$this->request` — `Core_Request` (`method`, `uri`, `path`, `get`, `post`, `input`, `all`, `only`, `except`, `has`, `isPost`, `isGet`, `cookie`, `file`, `hasFile`, `ip`, `header`)
- `$this->response` — `Core_Response` (`status`, `header`, `redirect`, `json($data,$status)`, `cookie(...)`)
- `$this->session` — `Core_Session` (`get`, `set`, `has`, `remove/forget`, `pull`, `all`, `clear`, `regenerate`, `destroy`, `flash`, `getFlash`, `id`, `setErrors/errors/getError/clearErrors`, `setOld/old/clearOld`)
- `$this->view($template, $data)` / `$this->render($template, $data)`
- `$this->db()` — lazy `Core_Database`
- `$this->redirect($url)`

## Global shorthand helpers

Available after boot (PHP 5.0-safe):

- HTTP/views: `request()`, `response()`, `input()`, `is_post()`, `is_get()`, `view()`, `render()`, `redirect()`, `redirect_back()`, `back()`, `json()`, `abort()`, `url()`, `route()`
- App: `config()`, `db()`, `model('User')` (or `model('UserModel')`), `logger($message,$level)`
- Session/flash: `session()`, `session_get/set/has/remove/pull/regen/destroy_safe`, `session_flash()`, `flash()`
- Forms/validation: `old()`, `errors()`, `with_input()`, `with_errors()`, `validate($data,$rules)`
- Security/auth: `csrf_token()`, `csrf_field()`, `csrf_valid()`, `escape()`, `hash_password()`, `verify_password()`, `auth_check()`, `auth_id()`, `auth_user($model)`
- Layout helper: `Core_View::make($template, $data, $layout)` renders a view into `$content` then the layout

```php
if (!is_post()) { return json(array('error' => 'Method not allowed'), 405); }
if (!csrf_valid()) { return json(array('error' => 'Invalid CSRF token'), 403); }

$name = trim((string)input('name', ''));
$id = model('User')->create(array('name' => $name));
return json(array('saved' => $id ? true : false, 'id' => $id));
```

## Views

Plain PHP templates in `app/views/`. Views receive extracted data plus auto-injected `csrf_token` and `csrf_field` when available.

```php
<h1><?php echo escape($title); ?></h1>
<form method="post" action="/users/save">
  <?php echo $csrf_field; ?>
  <input name="name" maxlength="255">
</form>
```

- Escape untrusted output with `escape()` / `Core_View::escape()` / `htmlspecialchars`.
- View names are sanitized and confined to `app/views/`.
- No layout engine; compose via includes/partials or rendered strings.

## Sessions

Sessions start automatically during `Compat::boot()` with secure cookie settings.

```php
// login
session_regen();
session_set('user_id', $user['id']);
redirect('/dashboard');

// check / flash
if (!session_has('user_id')) { redirect('/login'); }
session_flash('notice', 'Saved');
$msg = flash('notice'); // one-time read

// logout
session_destroy_safe();
redirect('/login');
```

## Validation & old input

```php
$data = array('email' => input('email'), 'name' => input('name'));
$errs = validate($data, array('email' => 'required|email', 'name' => 'required|min:2|max:100'));
if (count($errs)) { with_errors($errs); with_input($data); return redirect_back('/form'); }

echo old('email');          // repopulate input
echo errors('email');       // field error
```

Views also receive `$errors` (from session) and can use `route('name', array($id))` / `url('/path')` for links.

## CSRF

Include `<?php echo $csrf_field; ?>` (or `csrf_field()`) in forms and verify with `csrf_valid()` in state-changing handlers. You can also set `config/app.php` `csrf_protect => true` to enforce CSRF automatically for POST/PUT/PATCH/DELETE (manual checks still fine).

