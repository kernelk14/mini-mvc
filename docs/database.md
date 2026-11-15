# Database

Minimal DB wrapper with a unified API and automatic driver selection.

## Configuration (`config/database.php`)
```php
return array(
    'driver' => 'auto', // auto | pdo | mysqli | mysql
    'type'   => 'mysql', // mysql | sqlite
    'host' => 'localhost', 'port' => null,
    'name' => 'app', 'user' => 'root', 'pass' => '', 'charset' => 'utf8',
    'path' => BASE_PATH . '/storage/app/database.sqlite', // sqlite
);
```

## Driver selection (smart scan)
- **MySQL/MariaDB:** PDO (`pdo_mysql`) → `mysqli` → legacy `mysql` (if available).
- **SQLite:** PDO (`pdo_sqlite`).

## Usage
```php
$db = new Core_Database(mini_config('database'));
$res = $db->query('SELECT * FROM users WHERE id = ?', array($id));
$row = $db->fetch($res);
$rows = $db->fetchAll($res);
```
Models/controllers can use `Core_Model`/`$this->db()` which read from `config/database.php`.

## API
`connect/isConnected`, `query($sql,$params)`, `fetch/fetchAll`, `escape`, `lastInsertId`, `error`, `close`.

## Notes
- Not an ORM; keep SQL explicit. PDO uses prepared statements when params are provided; mysqli/mysql paths interpolate escaped `?` params.
- Legacy `mysql_*` is a fallback only where present (removed in PHP 7+). SQLite requires PDO sqlite.
