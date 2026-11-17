# Security

Baseline hardening for a small PHP 5.0+ MVC core. You must still follow secure coding practices in your application.

## Built-in measures

- **Secure sessions:** started at boot with HttpOnly cookies, SameSite=Lax, and the `secure` flag when on HTTPS. Use `Core_Session` / `$this->session` / `session_*` helpers (`get/set/has/remove/pull/regenerate/destroy/flash/getFlash`).
- **CSRF:** `csrf_token()`, `csrf_field()`, `csrf_valid()` (`mini_csrf_*` aliases). Views auto-receive `csrf_token` and `csrf_field`. Example: `UserController::save` and `app/views/user/index.php`. Optional automatic enforcement for POST/PUT/PATCH/DELETE via `config/app.php` `csrf_protect => true`.
- **Output escaping:** `escape()` / `Core_View::escape()` for untrusted output in templates.
- **View path safety:** template names are sanitized and confined to `app/views/` (blocks traversal).
- **Dispatch safety:** controller/action names are validated (allowlist), actions must be public/callable on `Core_Controller` subclasses; internal/base methods are not routable. Path+method mismatches return **405** with `Allow`.
- **Safe errors/404s:** internal details are shown only when `config/app.php` `debug` is true.
- **Redirect safety:** blocks header injection and external/open redirects (relative URLs only); relative redirects are auto-prefixed with the detected base URL for subfolder installs.
- **Security headers:** `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: same-origin`, `X-XSS-Protection` (app + root `.htaccess`).
- **Directory hardening:** `.htaccess` deny rules for `config/`, `system/`, `storage/`; `Options -Indexes` in root `.htaccess`.
- **Database:** parameterized `query/execute($sql,$params)` API (PDO prepared statements when available; escaped interpolation for mysqli/mysql). Always pass user input as parameters.
- **Config loading:** `config()` / `mini_config()` restrict names to `[A-Za-z0-9_-]+`.
- **Error exposure:** display errors disabled by default; enable `debug` only in development.

## Helpers

- Auth: `hash_password()`, `verify_password()`, `auth_check()`, `auth_id()`, `auth_user($model)` (session-based, pairs with your user model).
- Validation: `validate($data, $rules)` returns an errors array (rules: `required,email,min:n,max:n,numeric,confirmed`); pair with `with_errors()/with_input()/old()/errors()`.

## Recommendations

- Keep `debug` false in production and use strong DB credentials with least privilege.
- Validate and constrain all input (type, length, format) before use or storage.
- Add authentication/authorization as needed and protect state-changing routes with CSRF checks (manual or `csrf_protect`).
- Serve over HTTPS in production.
- Keep PHP and extensions updated; avoid legacy `mysql_*` where possible.
- Handle file uploads, shell access, and deserialization with care if introduced.
