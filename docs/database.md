# Database

Minimal DB wrapper with a unified API and automatic runtime driver selection.

## Configuration (`config/database.php`)

```php
return array(
    'driver' => 'auto', // auto | pdo | mysqli | mysql
    'type'   => 'mysql', // mysql | sqlite

    // MySQL / MariaDB
    'host' => 'localhost',
    'port' => null,
    'name' => 'app',
    'user' => 'root',
    'pass' => '',
    'charset' => 'utf8',

    // SQLite (when type = sqlite)
    'path' => BASE_PATH . '/storage/app/database.sqlite',
);
```

## Driver selection

- **MySQL/MariaDB:** PDO (`pdo_mysql`) → `mysqli` → legacy `mysql` (if available)
- **SQLite:** PDO (`pdo_sqlite`)
- Chosen from runtime capabilities (`Compat_Version`), unless `driver` is forced.

## Direct API

```php
$db = db(); // or new Core_Database(config('database'))

$res  = $db->query('SELECT * FROM users WHERE id = ?', array($id));
$row  = $db->fetch($res);
$rows = $db->fetchAll($res);

$ok   = $db->execute('DELETE FROM users WHERE id = ?', array($id));
$newId = $db->lastInsertId();
$err  = $db->error();
```

Methods: `connect/isConnected`, `query($sql,$params)`, `execute($sql,$params)`, `fetch/fetchAll`, `escape`, `lastInsertId`, `error`, `close`, and transactions: `beginTransaction()`, `commit()`, `rollBack()`, `transaction($callback)`.

```php
db()->transaction(function($db) {
    $db->execute('UPDATE accounts SET balance = balance - ? WHERE id = ?', array(10, 1));
    $db->execute('UPDATE accounts SET balance = balance + ? WHERE id = ?', array(10, 2));
    return true; // return false to rollback
});
```

## Models

```php
<?php
class UserModel extends Core_Model
{
    var $table = 'users';
}

$m = model('User');

$all  = $m->all(100);
$one  = $m->find($id);
$one  = $m->first('email = ?', array($email));
$rows = $m->where('active = ?', array(1), 50);

$id   = $m->create(array('name' => 'Ada', 'email' => 'ada@example.com'));
$ok   = $m->updateById($id, array('name' => 'Ada L.'));
$ok   = $m->deleteById($id);
```

Base helpers: `all($limit)`, `find($id)`, `first($whereSql,$params)`, `where($whereSql,$params,$limit)`, `create($data)`, `updateById($id,$data)`, `deleteById($id)`, `query($sql,$params)`.
`create`/`updateById` accept simple column names (`A-Za-z0-9_`).

## Notes

- Not an ORM; keep SQL explicit.
- Always pass untrusted values as parameters, never concatenated into SQL.
- PDO uses prepared statements when params are provided; mysqli/mysql paths interpolate escaped placeholders.
- Legacy `mysql_*` is fallback-only where present (removed in PHP 7+).
