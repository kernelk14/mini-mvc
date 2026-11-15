<?php
class UserModel extends Core_Model
{
    var $table = 'users';

    function create($name)
    {
        $res = $this->query('INSERT INTO users (name) VALUES (?)', array($name));
        if ($res === false) { return false; }
        return $this->db->lastInsertId();
    }
}
