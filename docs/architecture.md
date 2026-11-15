# Architecture

## Request lifecycle
1. **Front controller** (`public/index.php`) defines `BASE_PATH` and loads `system/compat/Compat.php`.
2. **Compat boot** (`Compat::boot`): scans runtime capabilities (`Compat_Version`), loads polyfills, registers autoloader, defines route helpers.
3. **App init** (`Core_App`): loads app config, creates router.
4. **Routes load**: `Core_App` requires `config/routes.php`, which registers routes via `get()/post()/...`.
5. **Dispatch**: router matches `REQUEST_METHOD` + path; falls back to `/Controller/action/params`.
6. **Controller**: instantiated and action invoked with params.
7. **View/Response**: controller renders a PHP template or returns JSON/redirect.

## Directory roles
- `public/` — only web-exposed entry point and assets.
- `app/` — application code (controllers, models, views).
- `config/` — environment/app configuration arrays.
- `routes/` — HTTP route definitions.
- `storage/` — writable/runtime data (e.g. SQLite files).
- `system/compat/` — version detection, polyfills, autoload, bootstrap helpers.
- `system/core/` — framework runtime (App, Router, MVC base, DB, Request/Response).

## Extension points
- Add routes in `config/routes.php`.
- Extend `Core_Controller` and `Core_Model` for app behavior.
- Use `Compat_Version` flags to branch on capabilities.
- Swap DB config/driver; `Core_Database` provides a consistent API.

## Design goals
- **Lightweight:** small classes, no heavy reflection or DI container.
- **Fast path:** simple route map lookup, direct includes, one-time boot checks.
- **Portable:** 5.0-safe syntax with runtime compat wrappers.

