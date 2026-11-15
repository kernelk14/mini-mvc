# Mini MVC (PHP 5.0+)

A mini, lightweight, reusable PHP MVC framework for PHP 5.0 through 8.x. The engine is 5.0-safe and uses a compatibility layer to scan runtime capabilities and enable the best available features on old and modern PHP.

## Features
- **PHP 5.0-8.x:** single core without namespaces/closures/short arrays/traits.
- **Smart compat scan:** `system/compat/Version.php` detects capabilities; `Compat::boot()` wires shims/drivers.
- **Runtime wrappers/polyfills:** newer helper behavior where possible (not new syntax).
- **Dedicated routes:** `config/routes.php` with `get()/post()/put()/patch()/delete()/any()`.
- **Minimal MVC core:** front controller, router, controllers, views, request/response.
- **Minimal DB wrapper:** unified API, auto driver selection (PDO→mysqli→mysql) for MySQL/MariaDB and PDO for SQLite.
- **Security baseline:** secure session cookies, CSRF helpers, output escaping, safe dispatch/view loading, security headers, debug-gated errors.
- **Reusable engine:** drop `system/` into other apps or use the full skeleton.

## Structure
```text
public/                 Web root (index.php, .htaccess)
app/                    Application code
  controllers/
  models/
  views/
config/                 Configuration (app.php, database.php)

storage/                Runtime storage (app/)
system/                 Reusable framework engine
  compat/               Version scan, bootstrap, polyfills, autoload
  core/                 App, Router, Controller, View, DB, Model, etc.
docs/                   Documentation
```

## Quick start
1. Point your web server document root to `public/`.
2. Configure `config/app.php` and (optional) `config/database.php`.
3. Define routes in `config/routes.php`.
4. Add controllers in `app/controllers/`, models in `app/models/`, views in `app/views/`.

**`config/routes.php`**
```php
<?php
get('/', 'HomeController@index');
get('/users', 'UserController@index');
get('/users/show', 'UserController@show');
post('/users/save', 'UserController@save');
```

## Docs
- [Getting Started](docs/getting-started.md) · [Architecture](docs/architecture.md) · [Routing](docs/routing.md)
- [Controllers & Views](docs/controllers-views.md) · [Database](docs/database.md) · [Configuration](docs/configuration.md) · [Compatibility](docs/compatibility.md) · [Security](docs/security.md)

## Notes
- Runtime polyfills/wrappers cannot add new PHP syntax to PHP 5.0 (no closures/namespaces/`[]`, etc.). Write 5.0-safe app code if targeting 5.0.
- `driver=auto` selects the best available DB driver for the configured type.

