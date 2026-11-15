# Configuration

Configuration lives in `config/` and files return PHP arrays.

## App (`config/app.php`)

```php
<?php
return array(
    'name' => 'Mini MVC',
    'env' => 'development',
    'debug' => true,
    'base_url' => '',
    'timezone' => 'UTC',
);
```

- `debug`: controls error display and whether detailed internal errors are shown. Keep `false` in production.

## Database (`config/database.php`)

See [Database](database.md) for driver selection and options (MySQL/MariaDB and SQLite).

## Routes (`config/routes.php`)

Route definitions loaded during app boot:

```php
<?php
get('/', 'HomeController@index');
get('/users/{id}', 'UserController@show');
post('/users/save', 'UserController@save');
```

See [Routing](routing.md).

## Loading config

`config('name')` / `mini_config('name')` load `config/name.php` and return its array (available after `Compat::boot()`).

```php
$app = config('app');
$db  = config('database');
```

Config names are restricted to `[A-Za-z0-9_-]+`.

## Environment-specific settings

Use plain PHP logic or deploy-time overrides (e.g. `config/*.local.php`, kept out of VCS) for per-environment values.
