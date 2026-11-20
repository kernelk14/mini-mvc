<?php
/**
 * Minimal fluent query builder (PHP 5.0+ safe).
 * Builds parameterized SQL on top of Core_Database.
 */
class Core_Query
{
    var $db;
    var $table;
    var $primaryKey = 'id';
    var $model = null;

    var $_select = array('*');
    var $_wheres = array();
    var $_bindings = array();
    var $_order = array();
    var $_limit = null;
    var $_offset = null;

    function __construct($db, $table, $primaryKey = 'id', $model = null)
    {
        $this->Core_Query($db, $table, $primaryKey, $model);
    }

    function Core_Query($db, $table, $primaryKey, $model)
    {
        $this->db = $db;
        $this->table = $table;
        $this->primaryKey = $primaryKey ? $primaryKey : 'id';
        $this->model = $model;
    }

    function _reset()
    {
        $this->_select = array('*');
        $this->_wheres = array();
        $this->_bindings = array();
        $this->_order = array();
        $this->_limit = null;
        $this->_offset = null;
    }

    function select($cols)
    {
        if (is_array($cols)) {
            $this->_select = $cols;
        } else {
            $this->_select = func_get_args();
        }
        return $this;
    }

    function where($column, $op = null, $value = null)
    {
        if (is_array($column) && func_num_args() === 1) {
            foreach ($column as $k => $v) { $this->where($k, '=', $v); }
            return $this;
        }
        // raw: where('col = ?', array(...)) treated in builder as whereRaw
        if (func_num_args() === 2 && is_array($op)) {
            return $this->whereRaw($column, $op);
        }
        if (func_num_args() === 2) {
            $value = $op;
            $op = '=';
        }
        if ($op === null) { $op = '='; }
        $this->_wheres[] = $this->_col($column) . ' ' . $op . ' ?';
        $this->_bindings[] = $value;
        return $this;
    }

    function orWhere($column, $op = null, $value = null)
    {
        $prefix = count($this->_wheres) ? 'OR ' : '';
        if (is_array($column) && func_num_args() === 1) {
            $parts = array();
            foreach ($column as $k => $v) {
                $parts[] = $this->_col($k) . ' = ?';
                $this->_bindings[] = $v;
            }
            if (count($parts)) { $this->_wheres[] = $prefix . '(' . implode(' AND ', $parts) . ')'; }
            return $this;
        }
        if (func_num_args() === 2 && is_array($op)) {
            $this->_wheres[] = $prefix . '(' . $column . ')';
            $i = 0; while ($i < count($op)) { $this->_bindings[] = $op[$i]; $i++; }
            return $this;
        }
        if (func_num_args() === 2) { $value = $op; $op = '='; }
        if ($op === null) { $op = '='; }
        $this->_wheres[] = $prefix . $this->_col($column) . ' ' . $op . ' ?';
        $this->_bindings[] = $value;
        return $this;
    }

    function whereRaw($sql, $params = null)
    {
        if ($params === null) { $params = array(); }
        $this->_wheres[] = $sql;
        $i = 0; while ($i < count($params)) { $this->_bindings[] = $params[$i]; $i++; }
        return $this;
    }

    function whereIn($column, $values)
    {
        if (!is_array($values) || count($values) === 0) {
            $this->_wheres[] = '1 = 0';
            return $this;
        }
        $marks = array();
        $i = 0; while ($i < count($values)) { $marks[] = '?'; $this->_bindings[] = $values[$i]; $i++; }
        $this->_wheres[] = $this->_col($column) . ' IN (' . implode(', ', $marks) . ')';
        return $this;
    }

    function whereNull($column)
    {
        $this->_wheres[] = $this->_col($column) . ' IS NULL';
        return $this;
    }

    function whereNotNull($column)
    {
        $this->_wheres[] = $this->_col($column) . ' IS NOT NULL';
        return $this;
    }

    function orderBy($column, $dir = 'ASC')
    {
        $d = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';
        $this->_order[] = $this->_col($column) . ' ' . $d;
        return $this;
    }

    function limit($n)
    {
        $this->_limit = (int)$n;
        return $this;
    }

    function offset($n)
    {
        $this->_offset = (int)$n;
        return $this;
    }

    function take($n) { return $this->limit($n); }
    function skip($n) { return $this->offset($n); }

    function _col($c)
    {
        // allow simple identifiers and dotted table.col; otherwise pass through
        if (preg_match('/^[A-Za-z0-9_\.]+$/', $c)) { return $c; }
        return $c;
    }

    function _buildSelect()
    {
        $cols = is_array($this->_select) ? implode(', ', $this->_select) : '*';
        $sql = 'SELECT ' . $cols . ' FROM ' . $this->table;
        if (count($this->_wheres) > 0) {
            $sql .= ' WHERE ' . $this->_compileWheres();
        }
        if (count($this->_order) > 0) {
            $sql .= ' ORDER BY ' . implode(', ', $this->_order);
        }
        if ($this->_limit !== null) {
            $sql .= ' LIMIT ' . (int)$this->_limit;
            if ($this->_offset !== null) { $sql .= ' OFFSET ' . (int)$this->_offset; }
        } elseif ($this->_offset !== null) {
            // offset without limit - use large limit for portability
            $sql .= ' LIMIT 18446744073709551615 OFFSET ' . (int)$this->_offset;
        }
        return $sql;
    }

    function _compileWheres()
    {
        $out = '';
        $i = 0;
        while ($i < count($this->_wheres)) {
            $w = $this->_wheres[$i];
            if ($i === 0) {
                // strip leading OR/AND if any
                $w = preg_replace('/^(OR|AND)\s+/i', '', $w);
                $out .= $w;
            } else {
                if (preg_match('/^OR\s+/i', $w)) {
                    $out .= ' ' . $w;
                } else {
                    $out .= ' AND ' . $w;
                }
            }
            $i++;
        }
        return $out;
    }

    function get()
    {
        if (!$this->db) { return array(); }
        $sql = $this->_buildSelect();
        $res = $this->db->query($sql, $this->_bindings);
        return $this->db->fetchAll($res);
    }

    function first()
    {
        $prev = $this->_limit;
        $this->_limit = 1;
        $rows = $this->get();
        $this->_limit = $prev;
        if (is_array($rows) && count($rows) > 0) { return $rows[0]; }
        return null;
    }

    function value($column)
    {
        $row = $this->select(array($column))->first();
        if ($row && is_array($row) && array_key_exists($column, $row)) { return $row[$column]; }
        return null;
    }

    function pluck($column)
    {
        $rows = $this->select(array($column))->get();
        $out = array();
        $i = 0;
        while ($i < count($rows)) {
            if (isset($rows[$i][$column])) { $out[] = $rows[$i][$column]; } else { $out[] = null; }
            $i++;
        }
        return $out;
    }

    function count($column = '*')
    {
        if ($column === null || $column === '') { $column = '*'; }
        if (!$this->db) { return 0; }
        $sql = 'SELECT COUNT(' . $column . ') AS aggregate FROM ' . $this->table;
        if (count($this->_wheres) > 0) { $sql .= ' WHERE ' . $this->_compileWheres(); }
        $res = $this->db->query($sql, $this->_bindings);
        $row = $this->db->fetch($res);
        return $row && isset($row['aggregate']) ? (int)$row['aggregate'] : 0;
    }

    function exists()
    {
        return $this->count('*') > 0;
    }

    function insert($data)
    {
        if (!$this->db || !is_array($data) || count($data) === 0) { return false; }
        $cols = array(); $marks = array(); $vals = array();
        foreach ($data as $k => $v) {
            if (!preg_match('/^[A-Za-z0-9_]+$/', $k)) { continue; }
            $cols[] = $k; $marks[] = '?'; $vals[] = $v;
        }
        if (count($cols) === 0) { return false; }
        $sql = 'INSERT INTO ' . $this->table . ' (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $marks) . ')';
        $res = $this->db->query($sql, $vals);
        if ($res === false) { return false; }
        return $this->db->lastInsertId();
    }

    function update($data)
    {
        if (!$this->db || !is_array($data) || count($data) === 0) { return false; }
        $sets = array(); $vals = array();
        foreach ($data as $k => $v) {
            if (!preg_match('/^[A-Za-z0-9_]+$/', $k)) { continue; }
            $sets[] = $k . ' = ?'; $vals[] = $v;
        }
        if (count($sets) === 0) { return false; }
        $sql = 'UPDATE ' . $this->table . ' SET ' . implode(', ', $sets);
        if (count($this->_wheres) > 0) { $sql .= ' WHERE ' . $this->_compileWheres(); }
        $i = 0; while ($i < count($this->_bindings)) { $vals[] = $this->_bindings[$i]; $i++; }
        return $this->db->execute($sql, $vals);
    }

    function delete()
    {
        if (!$this->db) { return false; }
        $sql = 'DELETE FROM ' . $this->table;
        if (count($this->_wheres) > 0) { $sql .= ' WHERE ' . $this->_compileWheres(); }
        return $this->db->execute($sql, $this->_bindings);
    }

    function toSql()
    {
        return $this->_buildSelect();
    }

    function getBindings()
    {
        return $this->_bindings;
    }
}
