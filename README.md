# Mini MVC

> A mini, lightweight, and reusable PHP MVC framework built to run from **PHP 5.0** all the way to modern **PHP 8.x**.

Mini MVC is a small app skeleton + drop-in engine (`system/`) with a smart compatibility layer that detects runtime capabilities and uses the best available features without sacrificing portability.

## Highlights

- **Wide compatibility** — single PHP 5.0-safe core (no namespaces, closures, short arrays, traits, or newer syntax requirements)
- **Smart runtime scan** — `system/compat/Version.php` inspects capabilities and `Compat::boot()` wires polyfills, autoloading, and helpers
- **Familiar MVC flow** — front controller, router, controllers, views, request/response, sessions
- **Dedicated routes** — `get()/post()/...` in `config/routes.php` with exact and `{param}` segment routes
- **Short app API** — helpers like `view()`, `json()`, `redirect()`, `input()`, `model()`, `session_*()`, `csrf_*()` on top of the core
- **Minimal DB wrapper** — unified API with auto driver selection (PDO → mysqli → mysql for MySQL/MariaDB, PDO for SQLite)
- **Model conveniences** — base helpers for `find`, `first`, `all`, `where`, `create`, `updateById`, `deleteById`, `query`
- **Security baseline** — secure session cookies, CSRF helpers, output escaping, safe dispatch/view loading, security headers, debug-gated errors
- **Reusable engine** — use the full skeleton or drop `system/` into an existing project

## Requirements

- PHP **5.0+** (feature availability depends on your PHP build/extensions)
- A web server with URL rewrites recommended (Apache `.htaccess` included), or PHP built-in server for development

## Quick Start

1. **Point your web server document root to the project root** (where `index.php` lives)
2. **Configure** `config/app.php` and (optional) `config/database.php`
3. **Define routes** in `config/routes.php`
4. **Add app code** in `app/controllers/`, `app/models/`, and `app/views/`

Run locally with PHP's built-in server:

```bash
php -S localhost:8000
```

## Example

**`config/routes.php`**
```php
<?php
get('/', 'HomeController@index');
get('/users', 'UserController@index');
get('/users/{id}', 'UserController@show');
post('/users/save', 'UserController@save');
```

**`app/controllers/UserController.php`**
```php
<?php
class UserController extends Core_Controller
{
    function show($id)
    {
        $user = model('User')->find($id);
        return view('user/show', array('user' => $user, 'id' => $id));
    }

    function save()
    {
        if (!is_post()) { return json(array('error' => 'Method not allowed'), 405); }
        if (!csrf_valid()) { return json(array('error' => 'Invalid CSRF token'), 403); }

        $name = trim((string)input('name', ''));
        $id = model('User')->create(array('name' => $name));
        return json(array('saved' => $id ? true : false, 'id' => $id));
    }
}
```

## Project Structure

```text
index.php               Front controller
.htaccess               Web server rewrites and headers
app/                    Application code
  controllers/
  models/
  views/
config/                 Configuration and routes
  app.php
  database.php
  routes.php
storage/                Runtime/storage data
  app/
system/                 Reusable framework engine
  compat/               Version scan, bootstrap, polyfills, autoload, helpers
  core/                 App, Router, MVC, DB, Request/Response, Session
docs/                   Documentation
```

## Database

Configure `config/database.php` (`driver => 'auto'` recommended). Supports **MySQL/MariaDB** and **SQLite** through a small unified wrapper:

```php
$user = model('User')->find($id);
$id = model('User')->create(array('name' => 'Ada'));
$rows = db()->fetchAll(db()->query('SELECT * FROM users WHERE active = ?', array(1)));
```

> See [docs/database.md](docs/database.md) for driver behavior and model helpers.

## Security Notes

- Runtime polyfills/helpers cannot add **new PHP syntax** to PHP 5.0; keep app code 5.0-safe if targeting 5.0.
- Keep `debug => false` in production and serve over HTTPS.
- Use CSRF helpers for state-changing requests and always pass untrusted values as query parameters.

## Documentation

- [Getting Started](docs/getting-started.md)
- [Architecture](docs/architecture.md)
- [Routing](docs/routing.md)
- [Controllers & Views](docs/controllers-views.md)
- [Database](docs/database.md)
- [Configuration](docs/configuration.md)
- [Compatibility](docs/compatibility.md)
- [Security](docs/security.md)

## License

[MIT](LICENSE)
