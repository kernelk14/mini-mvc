# Mini MVC

> A mini, lightweight, and reusable PHP MVC framework built to run from **PHP 5.0** all the way to modern **PHP 8.x**.

Mini MVC is a small app skeleton + drop-in engine (`system/`) with a smart compatibility layer that detects runtime capabilities and uses the best available features without sacrificing portability.

## Highlights

- **Wide compatibility** — single PHP 5.0-safe core (no namespaces, closures, short arrays, traits, or newer syntax requirements)
- **Smart runtime scan** — `system/compat/Version.php` inspects capabilities and `Compat::boot()` wires polyfills, autoloading, and helpers
- **Familiar MVC flow** — front controller, router, controllers, views, request/response
- **Dedicated routes** — simple `get()/post()/put()/patch()/delete()/any()` definitions in `config/routes.php`
- **Minimal DB wrapper** — unified API with auto driver selection (PDO → mysqli → mysql for MySQL/MariaDB, PDO for SQLite)
- **Security baseline** — secure session cookies, CSRF helpers, output escaping, safe dispatch/view loading, security headers, debug-gated errors
- **Reusable engine** — use the full skeleton or drop `system/` into an existing project

## Requirements

- PHP **5.0+** (feature availability depends on your PHP build/extensions)
- A web server with URL rewrites recommended (Apache `.htaccess` included), or PHP built-in server for development

## Quick Start

1. **Point your document root to `public/`**
2. **Configure** `config/app.php` and (optional) `config/database.php`
3. **Define routes** in `config/routes.php`
4. **Add app code** in `app/controllers/`, `app/models/`, and `app/views/`

Run locally with PHP's built-in server:

```bash
php -S localhost:8000 -t public
```

## Example

**`config/routes.php`**
```php
<?php
get('/', 'HomeController@index');
get('/users', 'UserController@index');
get('/users/show', 'UserController@show');
post('/users/save', 'UserController@save');
```

**`app/controllers/HomeController.php`**
```php
<?php
class HomeController extends Core_Controller
{
    function index()
    {
        $this->view('home/index', array(
            'title' => 'Home',
            'message' => 'Welcome'
        ));
    }
}
```

## Project Structure

```text
public/                 Web root (front controller, .htaccess)
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
  compat/               Version scan, bootstrap, polyfills, autoload
  core/                 App, Router, MVC base, DB, Request/Response
docs/                   Documentation
```

## Database

Configure `config/database.php` (`driver => 'auto'` recommended). Supports **MySQL/MariaDB** and **SQLite** through a small unified wrapper:

```php
$db = new Core_Database(mini_config('database'));
$res = $db->query('SELECT * FROM users WHERE id = ?', array($id));
$row = $db->fetch($res);
```

> See [docs/database.md](docs/database.md) for schema examples and driver behavior.

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
