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
    // var $primaryKey = 'id'; // default
}

$m = model('User');

$all  = $m->all(100);
$one  = $m->find($id);
$one  = $m->first('email = ?', array($email));          // legacy raw WHERE
$rows = $m->where('active = ?', array(1), 50);          // legacy raw WHERE, returns array

$id   = $m->create(array('name' => 'Ada', 'email' => 'ada@example.com'));
$ok   = $m->updateById($id, array('name' => 'Ada L.'));
$ok   = $m->deleteById($id);
```

Base helpers (BC): `all($limit)`, `find($id)`, `first($whereSql,$params)`, `where($whereSql,$params,$limit)`, `create($data)`, `updateById($id,$data)`, `deleteById($id)`, `query($sql,$params)`. `create`/`update*` accept simple column names (`A-Za-z0-9_`).

## Query builder (fluent)

`$m->query()` / `$m->newQuery()` returns a chainable `Core_Query` (parameterized, returns arrays). The legacy `where/first` methods above execute immediately and keep their raw-SQL signatures for compatibility.

```php
$m = model('User');

$user  = $m->query()->where('email', $email)->first();
$users = $m->query()->where('active', 1)
                   ->where('age', '>=', 18)
                   ->orderBy('created_at', 'desc')
                   ->limit(10)->get();

$users = $m->query()->where(array('active' => 1, 'role' => 'admin'))->get();
$users = $m->query()->whereIn('role', array('admin', 'editor'))->get();
$users = $m->query()->whereNull('deleted_at')->get();
$users = $m->query()->whereRaw('YEAR(created_at) = ?', array(2026))->get();

$n  = $m->query()->where('active', 1)->count();
$ok = $m->query()->where('email', $email)->exists();
$val  = $m->query()->where('id', 1)->value('email');
$names = $m->query()->where('active', 1)->pluck('name');

$id = $m->query()->insert(array('name' => 'Ada'));
$ok = $m->query()->where('id', $id)->update(array('name' => 'Ada L.'));
$ok = $m->query()->where('active', 0)->delete();
```

Builder methods include `select`, `where`/`orWhere`/`whereRaw`/`whereIn`/`whereNull`/`whereNotNull`, `orderBy`, `limit`/`offset` (`take`/`skip`), `get`, `first`, `value`, `pluck`, `count`, `exists`, `insert`, `update`, `delete`, `paginate`, `toSql`, `getBindings`. All values are bound as parameters.

## Pagination

```php
$p = model('User')->query()->where('active', 1)
        ->orderBy('id', 'desc')
        ->paginate(15); // perPage, optional $page, optional $pageParam='page'

foreach ($p->items() as $user) { /* $user['name'] */ }
echo $p->links(); // HTML nav (preserves existing query params)
```

`paginate($perPage=15, $page=null, $pageParam='page')` returns `Core_Paginator`. It reads `input($pageParam)` by default. Paginator helpers: `items()`, `total()`, `perPage()`, `currentPage()`, `lastPage()`, `count()`, `isEmpty()`, `hasPages()`, `hasMore()`, `onFirstPage()`, `from()`, `to()`, `url($page)`, `previousPageUrl()`, `nextPageUrl()`, `setBaseUrl($url)`, `links($baseUrl=null,$window=2)`, `toArray()`. For APIs use `json($p->toArray())`.

## Notes

- Not an ORM; keep SQL explicit.
- Always pass untrusted values as parameters, never concatenated into SQL.
- PDO uses prepared statements when params are provided; mysqli/mysql paths interpolate escaped placeholders.
- Legacy `mysql_*` is fallback-only where present (removed in PHP 7+).
