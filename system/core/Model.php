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

    function create($data)
    {
        if ($this->table === '' || !is_array($data) || count($data) === 0) { return false; }
        $cols = array();
        $marks = array();
        $vals = array();
        foreach ($data as $k => $v) {
            if (!preg_match('/^[A-Za-z0-9_]+$/', $k)) { continue; }
            $cols[] = $k;
            $marks[] = '?';
            $vals[] = $v;
        }
        if (count($cols) === 0) { return false; }
        $sql = 'INSERT INTO ' . $this->table . ' (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $marks) . ')';
        $res = $this->db->query($sql, $vals);
        if ($res === false) { return false; }
        return $this->db->lastInsertId();
    }

    function updateById($id, $data)
    {
        if ($this->table === '' || !is_array($data) || count($data) === 0) { return false; }
        $sets = array();
        $vals = array();
        foreach ($data as $k => $v) {
            if (!preg_match('/^[A-Za-z0-9_]+$/', $k)) { continue; }
            $sets[] = $k . ' = ?';
            $vals[] = $v;
        }
        if (count($sets) === 0) { return false; }
        $vals[] = $id;
        $sql = 'UPDATE ' . $this->table . ' SET ' . implode(', ', $sets) . ' WHERE id = ?';
        return $this->db->execute($sql, $vals);
    }

    function deleteById($id)
    {
        if ($this->table === '') { return false; }
        $sql = 'DELETE FROM ' . $this->table . ' WHERE id = ?';
        return $this->db->execute($sql, array($id));
    }

    function where($whereSql, $params = null, $limit = null)
    {
        if ($this->table === '' || $whereSql === null || $whereSql === '') { return array(); }
        if ($params === null) { $params = array(); }
        $sql = 'SELECT * FROM ' . $this->table . ' WHERE ' . $whereSql;
        if ($limit !== null && (int)$limit > 0) { $sql .= ' LIMIT ' . (int)$limit; }
        $res = $this->db->query($sql, $params);
        return $this->db->fetchAll($res);
    }
}
