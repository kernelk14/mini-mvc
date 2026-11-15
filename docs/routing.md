# Routing

Routes are defined in `config/routes.php`. It is loaded after compat boot so helper functions are available.

## Defining routes
```php
<?php
get('/', 'HomeController@index');
get('/users', 'UserController@index');
get('/users/show', 'UserController@show'); // e.g. /users/show?id=1
post('/users/save', 'UserController@save');

put('/users/update', 'UserController@update');
patch('/users/update', 'UserController@update');
delete('/users/delete', 'UserController@delete');
any('/health', 'HealthController@index');
```

- **Signature:** `get|post|put|patch|delete|any($path, 'Controller@action')`
- **Path:** matched against request path (query string stripped, trailing slash normalized except `/`).
- **Handler:** string `'Controller@action'`.

## Dispatch behavior
1. Exact method+path match in the registry (`ANY` matches all methods).
2. Fallback: `/Controller/action/param1/param2` (e.g. `/` → `HomeController@index`).
3. Missing controller/action returns 404.
