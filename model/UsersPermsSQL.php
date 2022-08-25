<?php

namespace S2lowLegacy\Model;

use S2lowLegacy\Lib\SQL;

class UsersPermsSQL extends SQL
{
    public const PERM_MODIFICATION = "RW";
    public const PERM_VISUALISATION = "RO";
    public const PERM_NONE = "NONE";

    public function getInfoPerms($module_id, $user_id)
    {
        $sql = "SELECT perm FROM users_perms WHERE module_id=? AND user_id=? ";
        return $this->queryOne($sql, $module_id, $user_id);
    }

    public function setPerms($module_id, $user_id, $perm)
    {
        if ($this->getInfoPerms($module_id, $user_id)) {
            $sql = "UPDATE users_perms SET perm=? WHERE module_id=? AND user_id=?";
            $this->query($sql, $perm, $module_id, $user_id);
        } else {
            $sql = "INSERT INTO users_perms (module_id,user_id,perm) VALUES (?,?,?)";
            $this->query($sql, $module_id, $user_id, $perm);
        }
    }
}
