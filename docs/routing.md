# Routing

Routes are defined in `config/routes.php`. The file is loaded after `Compat::boot()` so route helpers are available.

## Defining routes

```php
<?php
get('/', 'HomeController@index');
get('/users', 'UserController@index');
get('/users/show/{id}', 'UserController@show');
post('/users/save', 'UserController@save');

put('/users/{id}', 'UserController@update');
patch('/users/{id}', 'UserController@update');
delete('/users/{id}', 'UserController@destroy');
any('/health', 'HealthController@index');
```

## Notes

- **Helpers:** `get`, `post`, `put`, `patch`, `delete`, `any($path, 'Controller@action')`
- **Handlers:** `Controller@action`
- **Path matching:** exact paths and segment parameters like `{id}`. Captured values are passed to the action in order.
- **Normalization:** query string is ignored for matching; trailing slash is normalized (except `/`).

## Dispatch behavior

1. Match a registered route by method and path (exact or `{param}`); `ANY` matches all methods.
2. Fallback: `/Controller/action/param1/param2` (e.g. `/` → `HomeController@index`).
3. Controller/action names are validated before dispatch; missing or invalid targets return 404.

## Examples

Route parameter:

```php
get('/users/show/{id}', 'UserController@show');
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

Query-string style is also fine (`/users/show?id=1`) via `input('id')`.
