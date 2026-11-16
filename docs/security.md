# Security

Baseline hardening for a small PHP 5.0+ MVC core. You must still follow secure coding practices in your application.

## Built-in measures

- **Secure sessions:** started at boot with HttpOnly cookies, SameSite=Lax, and the `secure` flag when on HTTPS. Use `Core_Session` / `$this->session` / `session_*` helpers (`get/set/has/remove/pull/regenerate/destroy/flash/getFlash`).
- **CSRF:** `csrf_token()`, `csrf_field()`, `csrf_valid()` (`mini_csrf_*` aliases). Views auto-receive `csrf_token` and `csrf_field`. Example: `UserController::save` and `app/views/user/index.php`.
- **Output escaping:** `escape()` / `Core_View::escape()` for untrusted output in templates.
- **View path safety:** template names are sanitized and confined to `app/views/` (blocks traversal).
- **Dispatch safety:** controller/action names are validated (allowlist), actions must be public/callable on `Core_Controller` subclasses; internal/base methods are not routable.
- **Safe errors/404s:** internal details are shown only when `config/app.php` `debug` is true.
- **Redirect safety:** blocks header injection and external/open redirects (relative URLs only).
- **Security headers:** `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: same-origin`, `X-XSS-Protection` (app + root `.htaccess`).
- **Directory hardening:** `.htaccess` deny rules for `config/`, `system/`, `storage/`; `Options -Indexes` in root `.htaccess`.
- **Database:** parameterized `query/execute($sql,$params)` API (PDO prepared statements when available; escaped interpolation for mysqli/mysql). Always pass user input as parameters.
- **Config loading:** `config()` / `mini_config()` restrict names to `[A-Za-z0-9_-]+`.
- **Error exposure:** display errors disabled by default; enable `debug` only in development.

## Recommendations

- Keep `debug` false in production and use strong DB credentials with least privilege.
- Validate and constrain all input (type, length, format) before use or storage.
- Add authentication/authorization as needed and protect state-changing routes with CSRF checks.
- Serve over HTTPS in production.
- Keep PHP and extensions updated; avoid legacy `mysql_*` where possible.
- Handle file uploads, shell access, and deserialization with care if introduced.
