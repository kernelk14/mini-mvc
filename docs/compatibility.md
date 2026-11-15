# Compatibility

## Target
- Core syntax compatible with **PHP 5.0+** through 8.x.
- Runtime adapts via `system/compat/Version` (smart scan) and `Compat::boot()`.

## What the compat layer does
- **Capability scan:** PHP version normalization, SPL autoload, PDO/mysqli/mysql, PDO drivers, JSON, mbstring, Exception.
- **Autoloading:** `spl_autoload_register` when available; `__autoload` fallback for very old runtimes. Resolves `Core_*`/`Compat_*`, app controllers/models, and underscore paths.
- **Polyfills/helpers:** runtime wrappers for missing conveniences (e.g. `str_contains`, `str_starts_with/ends_with`, `array_key_first/last`, minimal json helpers).
- **Driver selection:** best DB driver for configured type (MySQL: PDO mysql → mysqli → mysql; SQLite: PDO sqlite).
- **Bootstrap:** route helpers `get/post/put/patch/delete/any`, `mini_base_path`, `mini_config`.

## Syntax limits (cannot be polyfilled)
PHP 5.0 cannot parse newer syntax, so the framework avoids: namespaces, traits, closures/arrow functions, short arrays `[]`, `__DIR__`, type declarations/typed properties, `??`, etc. Use 5.0-safe style in app code if targeting 5.0.

## Reuse
`system/` is the reusable engine: `require BASE_PATH . '/system/compat/Compat.php'; Compat::boot(BASE_PATH); (new Core_App())->run();` with your `config/`, `routes/`, and `app/` in place.

