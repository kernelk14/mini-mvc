# Security

This framework includes baseline hardening for a small PHP 5.0+ MVC core. You must still follow secure coding practices in your app.

## Built-in measures
- **Secure sessions (compat boot):** session started with HttpOnly cookies, SameSite=Lax, and `secure` flag when on HTTPS.
- **CSRF helpers:** `mini_csrf_token()`, `mini_csrf_field()`, `mini_csrf_check($token)`; example POST flow in `UserController::save` and `app/views/user/index.php`.
- **Output escaping:** `Core_View::escape()`/`Core_View::e()`; templates should escape all untrusted output (e.g. `htmlspecialchars`/`Core_View::e`).
- **View path safety:** template names sanitized and confined to `app/views/` (blocks traversal).
- **Dispatch safety:** controller/action names validated (allowlist pattern), actions must be public/callable on `Core_Controller` subclasses; internal/base methods are not routable.
- **Safe 404s:** internal details shown only when `config/app.php` `debug` is true.
- **Redirect safety:** response redirect blocks header injection and external/open redirects (relative URLs only).
- **Security headers:** `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: same-origin`, `X-XSS-Protection` (app + root `.htaccess`).
- **Directory hardening:** `.htaccess` deny rules for `config/`, `system/`, `storage/`; `Options -Indexes` in root `.htaccess`.
- **Database:** parameterized `query($sql, $params)` API (PDO prepared statements when available; escaped interpolation for mysqli/mysql). Always pass user input as params, never concatenated SQL.
- **Config loading:** `mini_config()` restricts names to `[A-Za-z0-9_-]+`.
- **Error exposure:** display errors disabled by default; enable `debug => true` only in development.

## Recommendations
- Keep `debug` false in production and use strong DB credentials/least privilege.
- Validate and constrain all input (types, length, format) before use/storage.
- Add authentication/authorization as needed; protect state-changing routes with CSRF checks.
- Serve over HTTPS in production; set a strong session configuration as appropriate.
- Keep PHP and extensions updated; avoid enabling legacy `mysql_*` where possible.
- Review file upload, shell, and deserialization usage carefully if added (none included by default).