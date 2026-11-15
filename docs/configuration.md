# Configuration

Configuration lives in `config/` and files return PHP arrays.

## App (`config/app.php`)
```php
return array(
    'name' => 'Mini MVC',
    'env' => 'development',
    'debug' => true,
    'base_url' => '',
    'timezone' => 'UTC',
);
```

## Database (`config/database.php`)
See [Database](database.md) (auto driver selection, MySQL/MariaDB and SQLite).

## Routes (`config/routes.php`)
Route definitions loaded during app boot:
```php
get('/', 'HomeController@index');
post('/users/save', 'UserController@save');
```
See [Routing](routing.md).

## Loading config
`mini_config('name')` loads `config/name.php` and returns its array (available after `Compat::boot()`).
```php
$app = mini_config('app');
$db  = mini_config('database');
```

## Environment-specific settings
Use plain PHP logic or deploy-time overrides (e.g. `config/*.local.php`, kept out of VCS) for per-environment values.
