# Architecture

## Request lifecycle

1. **Front controller** (`index.php`) defines `BASE_PATH` and loads `system/compat/Compat.php`.
2. **Compat boot** (`Compat::boot`): scans runtime capabilities (`Compat_Version`), loads polyfills, registers autoloading, defines route/helpers, and starts a secure session.
3. **App init** (`Core_App`): loads app config, creates the router, and sends security headers.
4. **Routes load:** `Core_App` requires `config/routes.php`, which registers routes via `get()/post()/...`.
5. **Dispatch:** router matches `REQUEST_METHOD` + path (exact or `{param}`), then falls back to `/Controller/action/params`.
6. **Controller:** instantiated and validated action invoked with params.
7. **View/Response:** render a template or return JSON/redirect.

## Directory roles

- `app/` — application code (controllers, models, views)
- `config/` — configuration and routes
- `storage/` — writable/runtime data (e.g. SQLite files)
- `system/compat/` — version detection, polyfills, autoload, bootstrap, helpers
- `system/core/` — framework runtime (App, Router, Controller, View, Model, DB, Request/Response, Session)

## Core components

- **Compat layer:** `Compat_Version`, `Compat`, global helpers (config, view, db, session, csrf, model, etc.)
- **Routing:** `Core_Router` registry and dispatch (exact + `{param}` + fallback)
- **MVC base:** `Core_Controller`, `Core_Model`, `Core_View`
- **HTTP:** `Core_Request`, `Core_Response`
- **State/DB:** `Core_Session`, `Core_Database` (driver auto-selection)

## Extension points

- Add routes in `config/routes.php`.
- Extend `Core_Controller` and `Core_Model` for app behavior.
- Use `Compat_Version` flags to branch on capabilities.
- Swap DB config/driver; `Core_Database` provides a consistent API.

## Design goals

- **Lightweight:** small classes, no heavy reflection or DI container.
- **Fast path:** simple route map lookup, direct includes, one-time boot checks.
- **Portable:** 5.0-safe syntax with runtime compat wrappers.
- **Secure-by-default basics:** safe dispatch/views, CSRF/session helpers, parameterized DB API, hardened headers.
