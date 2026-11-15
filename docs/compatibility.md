# Compatibility

## Target

- Core syntax compatible with **PHP 5.0+** through 8.x.
- Runtime adapts via `system/compat/Version` (smart scan) and `Compat::boot()`.

## What the compat layer does

- **Capability scan:** PHP version normalization, SPL autoload, PDO/mysqli/mysql, PDO drivers, JSON, mbstring, Exception.
- **Autoloading:** `spl_autoload_register` when available; `__autoload` fallback for very old runtimes. Resolves `Core_*`/`Compat_*`, app controllers/models, and underscore paths.
- **Polyfills/helpers:** runtime wrappers for missing conveniences (e.g. `str_contains`, `str_starts_with/ends_with`, `array_key_first/last`, minimal json helpers).
- **Driver selection:** best DB driver for configured type (MySQL: PDO mysql → mysqli → mysql; SQLite: PDO sqlite).
- **Bootstrap/helpers:** route helpers (`get/post/put/patch/delete/any`), config/app helpers, session/CSRF, request/response/view/model/db shorthands, and secure session startup.

## Syntax limits (cannot be polyfilled)

PHP 5.0 cannot parse newer syntax, so the framework avoids: namespaces, traits, closures/arrow functions, short arrays `[]`, `__DIR__`, type declarations/typed properties, `??`, etc. Keep app code 5.0-safe if you target 5.0.

## Reuse

`system/` is the reusable engine:

```php
<?php
define('BASE_PATH', '/path/to/project');
require BASE_PATH . '/system/compat/Compat.php';
Compat::boot(BASE_PATH);
$app = new Core_App();
$app->run();
```

Use it with your `config/`, `app/`, and `storage/` as expected by this skeleton.
