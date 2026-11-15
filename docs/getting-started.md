# Getting Started

## 1. Install / reuse

**As an app skeleton:** copy the repository and build in `app/` and `config/`.

**As a drop-in engine:** copy `system/` into an existing project, set `BASE_PATH`, and bootstrap:

```php
<?php
define('BASE_PATH', '/path/to/project');
require BASE_PATH . '/system/compat/Compat.php';
Compat::boot(BASE_PATH);

$app = new Core_App();
$app->run();
```

Provide `config/*.php`, `config/routes.php`, and your `app/controllers|models|views` paths as used by this skeleton.

## 2. Web server

Point the document root to the project root (where `index.php` is).

- **Apache:** root `.htaccess` rewrites non-file requests to `index.php`.
- **Nginx (example):**
  ```nginx
  root /path/to/project;
  location / { try_files $uri $uri/ /index.php?$query_string; }
  location ~ \.php$ { include fastcgi_params; fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; fastcgi_pass 127.0.0.1:9000; }
  ```
- **PHP built-in server:** `php -S localhost:8000`

## 3. Configure

- `config/app.php` — app name, env, debug, base_url.
- `config/database.php` — DB driver/type and credentials (see [Database](database.md)).

## 4. Add a route, controller, model, and view

**Route** (`config/routes.php`):

```php
<?php
get('/hello', 'HomeController@hello');
get('/users/{id}', 'UserController@show');
post('/users/save', 'UserController@save');
```

**Controller** (`app/controllers/HomeController.php`):

```php
<?php
class HomeController extends Core_Controller
{
    function hello()
    {
        return view('home/index', array('title' => 'Hello', 'message' => 'Hi'));
    }
}
```

**Model** (`app/models/UserModel.php`):

```php
<?php
class UserModel extends Core_Model
{
    var $table = 'users';
}
```

Use `model('User')->all()`, `find($id)`, `first('email = ?', array($email))`, `where(...)`, `create(array(...))`, `updateById(...)`, `deleteById(...)`.

## 5. Run

Open `/` or `/users`. If DB is not configured, sample user pages show a notice but the framework still runs.
