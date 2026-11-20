<?php
/**
 * Minimal base model with fluent query builder support.
 * Legacy array/SQL APIs preserved for BC.
 */
class Core_Model
{
    var $db;
    var $table = '';
    var $primaryKey = 'id';

    function __construct()
    {
        $this->Core_Model();
    }

    function Core_Model()
    {
        $cfg = mini_config('database');
        $this->db = new Core_Database($cfg);
    }

    function ready()
    {
        return $this->db && $this->db->isConnected();
    }

    function dbError()
    {
        return $this->db ? $this->db->error() : 'Database not configured';
    }

    /**
     * Raw SQL query (BC): query($sql, $params)
     * Fluent builder: query() with no args returns Core_Query.
     */
    function query($sql = null, $params = null)
    {
        if ($sql === null) {
            return $this->newQuery();
        }
        if ($params === null) { $params = array(); }
        return $this->db->query($sql, $params);
    }

    function newQuery()
    {
        return new Core_Query($this->db, $this->table, $this->primaryKey, $this);
    }

    function builder() { return $this->newQuery(); }

    function all($limit = null)
    {
        if ($this->table === '') { return array(); }
        $q = $this->newQuery();
        if ($limit !== null && (int)$limit > 0) { $q->limit((int)$limit); }
        return $q->get();
    }

    function find($id)
    {
        if ($this->table === '' || $id === null) { return null; }
        return $this->newQuery()->where($this->primaryKey, $id)->first();
    }

    /**
     * BC: first($whereSql, $params) - raw WHERE string, executes immediately.
     * Builder: first() with no args after chained where() not possible on model directly;
     *          use query()->...->first(). This keeps legacy signature intact.
     */
    function first($whereSql = null, $params = null)
    {
        if ($this->table === '' || $whereSql === null || $whereSql === '') { return null; }
        if ($params === null) { $params = array(); }
        return $this->newQuery()->whereRaw($whereSql, $params)->first();
    }

    function create($data)
    {
        if ($this->table === '' || !is_array($data) || count($data) === 0) { return false; }
        return $this->newQuery()->insert($data);
    }

    function updateById($id, $data)
    {
        if ($this->table === '' || !is_array($data) || count($data) === 0) { return false; }
        return $this->newQuery()->where($this->primaryKey, $id)->update($data);
    }

    function deleteById($id)
    {
        if ($this->table === '') { return false; }
        return $this->newQuery()->where($this->primaryKey, $id)->delete();
    }

    /**
     * BC: where($whereSql, $params, $limit) - raw WHERE, executes and returns array.
     * For fluent builder use query()->where($col,$val)->get().
     */
    function where($whereSql, $params = null, $limit = null)
    {
        if ($this->table === '' || $whereSql === null || $whereSql === '') { return array(); }
        if ($params === null) { $params = array(); }
        $q = $this->newQuery()->whereRaw($whereSql, $params);
        if ($limit !== null && (int)$limit > 0) { $q->limit((int)$limit); }
        return $q->get();
    }
}
