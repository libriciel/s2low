<?php

require_once __DIR__ . "/UsersPermsSQL.class.php";

class ModuleSQL extends SQL
{
    public const ACTES_MODULE_NAME = 'actes';
    public const HELIOS_MODULE_NAME = 'helios';

    public function getInfoByName($name)
    {
        $sql = "SELECT * FROM modules WHERE name=?";
        return $this->queryOne($sql, $name);
    }

    public function getInfo($module_id)
    {
        $sql =  "SELECT * FROM modules WHERE id=?";
        return $this->queryOne($sql, $module_id);
    }

    public function getUsers($module_id, $authority_group_id = 0)
    {
        $sql = "SELECT DISTINCT users.id, users.givenname, users.name, users.email FROM users " .
            " LEFT JOIN users_perms ON users.id=users_perms.user_id " .
            " LEFT JOIN modules ON users_perms.module_id=modules.id " .
            " WHERE modules.id=? AND (users_perms.perm='RO' OR users_perms.perm='RW') AND users.status=1 ";
        $data = [$module_id];
        if ($authority_group_id) {
            $sql .= " AND users.authority_group_id=? ";
            $data[] = $authority_group_id;
        }
        return $this->query($sql, $data);
    }

    public function getInfoModuleAuthority($module_id, $authority_id)
    {
        $sql = "SELECT * FROM modules_authorities " .
                " WHERE module_id=? AND authority_id=? ";
        return $this->queryOne($sql, $module_id, $authority_id);
    }

    public function getInfoPerms($module_id, $user_id)
    {
        $sql = "SELECT perm FROM users_perms WHERE module_id=? AND user_id=? ";
        return $this->queryOne($sql, $module_id, $user_id);
    }

    public function hasDroit($module_id, $user_id, $droit_to_checked)
    {
        $droit = $this->getInfoPerms($module_id, $user_id);
        if ($droit == UsersPermsSQL::PERM_MODIFICATION) {
            return true;
        }
        return $droit == $droit_to_checked;
    }

    public function getModulesForUser($userInfo)
    {
        if (! $userInfo) {
            return array();
        }
        if ($userInfo['role'] == 'SADM') {
            $sql = "SELECT * FROM modules WHERE status=1" . " ORDER BY modules.name ";
            return $this->query($sql);
        }
        if ($userInfo['role'] == 'GADM') {
            $sql = "SELECT modules.* FROM modules " .
                " JOIN modules_authorities ON modules_authorities.module_id=modules.id " .
                " WHERE modules_authorities.authority_id=?  AND modules.status=1" .
                " ORDER BY modules.name ";
            return $this->query($sql, $userInfo['authority_id']);
        }

         $sql = "SELECT modules.* FROM modules " .
                " JOIN modules_authorities ON modules_authorities.module_id=modules.id " .
                " JOIN users_perms ON modules.id=users_perms.module_id " .
                " WHERE modules_authorities.authority_id=? " .
                " AND users_perms.user_id= ? " .
                " AND perm != 'NONE' " .
             " AND modules.status=1" .
             " ORDER BY modules.name ";

        return  $this->query($sql, $userInfo['authority_id'], $userInfo['id']);
    }

    public function getActiveModuleList()
    {
        $sql = "SELECT * FROM modules WHERE status=1 ORDER BY id ASC";
        return $this->query($sql);
    }
}
