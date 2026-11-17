# Routing

Routes are defined in `config/routes.php`. The file is loaded after `Compat::boot()` so route helpers are available.

## Defining routes

```php
<?php
get('/', 'HomeController@index', 'home');
get('/users', 'UserController@index');
get('/users/show/{id}', 'UserController@show', 'users.show');
post('/users/save', 'UserController@save');

put('/users/{id}', 'UserController@update');
patch('/users/{id}', 'UserController@update');
delete('/users/{id}', 'UserController@destroy');
any('/health', 'HealthController@index');
```

## Notes

- **Helpers:** `get`, `post`, `put`, `patch`, `delete`, `any($path, $handler, $name = null)`
- **Handlers:** `Controller@action`
- **Path matching:** exact paths and segment parameters like `{id}`. Captured values are passed to the action in order.
- **Normalization:** query string is ignored for matching; trailing slash is normalized (except `/`); subfolder base URL is stripped for installs under a path (e.g. XAMPP).
- **Named routes:** optional 3rd arg sets a name; generate URL with `route('users.show', array($id))` (auto-prefixed with base URL).

## Dispatch behavior

1. Match a registered route by method and path (exact or `{param}`); `ANY` matches all methods. `HEAD` is treated as `GET`.
2. If path matches but method does not, respond **405 Method Not Allowed** with `Allow` header.
3. Fallback: `/Controller/action/param1/param2` (e.g. `/` → `HomeController@index`).
4. Controller/action names are validated before dispatch; missing or invalid targets return 404.

## Examples

```php
get('/users/show/{id}', 'UserController@show', 'users.show');
$url = route('users.show', array(5)); // respects base_url/subfolder
```

```php
class UserController extends Core_Controller
{
    function show($id)
    {
        $user = model('User')->find($id);
        return view('user/show', array('user' => $user, 'id' => $id));
    }
}
```

Query-string style is also fine (`/users/show?id=1`) via `input('id')`. For servers without rewrites, `index.php?url=/path` and `PATH_INFO` are supported as fallbacks.
