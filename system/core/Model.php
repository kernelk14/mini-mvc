<?php
/**
 * Minimal base model.
 */
class Core_Model
{
    var $db;
    var $table = '';

    function __construct()
    {
        $this->Core_Model();
    }

    function Core_Model()
    {
        $cfg = mini_config('database');
        $this->db = new Core_Database($cfg);
    }

    function query($sql, $params = null)
    {
        if ($params === null) { $params = array(); }
        return $this->db->query($sql, $params);
    }

    function all($limit = null)
    {
        if ($this->table === '') { return array(); }
        $sql = 'SELECT * FROM ' . $this->table;
        if ($limit !== null && (int)$limit > 0) {
            $sql .= ' LIMIT ' . (int)$limit;
        }
        $res = $this->db->query($sql, array());
        return $this->db->fetchAll($res);
    }

    function find($id)
    {
        if ($this->table === '') { return null; }
        $sql = 'SELECT * FROM ' . $this->table . ' WHERE id = ? LIMIT 1';
        $res = $this->db->query($sql, array($id));
        return $this->db->fetch($res);
    }

    function first($whereSql, $params = null)
    {
        if ($this->table === '' || $whereSql === null || $whereSql === '') { return null; }
        if ($params === null) { $params = array(); }
        $sql = 'SELECT * FROM ' . $this->table . ' WHERE ' . $whereSql . ' LIMIT 1';
        $res = $this->db->query($sql, $params);
        return $this->db->fetch($res);
    }
}
