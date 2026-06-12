<?php

namespace S2lowLegacy\Model;

use S2lowLegacy\Lib\SQL;
use S2lowLegacy\Class\Module;

class ModuleSQL extends SQL
{
    public const ACTES_MODULE_NAME = 'actes';
    public const HELIOS_MODULE_NAME = 'helios';

    public function getById($id): ?Module
    {
        $row = $this->queryOne("SELECT * FROM modules WHERE id = ?", $id);
        if (!$row) {
            return null;
        }
        return new Module(
            id: (int)$row['id'],
            name: $row['name'],
            description: $row['description'],
            menu_entry: $row['menu_entry'],
            status: (int)$row['status']
        );
    }

    public function initByName(string $name): ?Module
    {
        $row = $this->queryOne("SELECT * FROM modules WHERE name = ?", $name);
        if (!$row) {
            return null;
        }
        return new Module(
            id: (int)$row['id'],
            name: $row['name'],
            description: $row['description'],
            menu_entry: $row['menu_entry'],
            status: (int)$row['status']
        );
    }

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

    public function getModulesForAuthority($authorityId): array
    {
        $sql = "SELECT modules_authorities.id, modules_authorities.module_id " .
            " FROM modules_authorities " .
            " LEFT JOIN modules ON modules_authorities.module_id=modules.id " .
            " WHERE modules_authorities.authority_id= ? AND modules.status=1 " .
            " ORDER BY modules.name";

        $result = $this->query($sql, $authorityId);
        $modules = [];
        foreach ($result as $row) {
            $modules[(int)$row["module_id"]] = true;
        }
        return $modules;
    }

    public function getLegacyModulesForUser($userId): array
    {
        $userInfo = $this->queryOne("SELECT id, role, authority_id FROM users WHERE id=?", $userId);
        if (!$userInfo) {
            return [];
        }
        $modules = $this->getModulesForUser($userInfo);
        $perms = [];
        foreach ($modules as $module) {
            $perms[(int)$module["id"]] = [
                "name" => $module["name"],
                "description" => $module["description"],
                "menu_entry" => $module["menu_entry"]
            ];
        }
        return $perms;
    }

    public function getActiveModulesNames(): array
    {
        $modules = $this->getActiveModulesList();
        $ret = [];
        foreach ($modules as $module) {
            $ret[$module["name"]] = $module["name"];
        }
        return $ret;
    }

    public function getActiveModulesIdName(): array
    {
        return $this->getModulesIdName(" WHERE status=1");
    }

    public function getModulesIdName(string $cond = ''): array
    {
        $modules = $this->getModulesList($cond);
        $tabModules = [];
        foreach ($modules as $module) {
            $tabModules[(int)$module["id"]] = $module["name"];
        }
        return $tabModules;
    }

    public function getActiveModulesList(): array
    {
        return $this->getModulesList(" WHERE status=1 ORDER BY name ASC");
    }

    public function getModulesList(string $cond = ""): array
    {
        $sql = "SELECT modules.id, modules.name, modules.description, modules.menu_entry, modules.status FROM modules " . $cond;
        $r = $this->query($sql);
        foreach ($r as $i => $module) {
            if (in_array($module['name'], array('actes','helios'))) {
                $specific_perms = array();
            } else {
                $specific_perms = array();
            }
            $r[$i]['specific_perms'] = $specific_perms;
        }
        return $r;
    }

    public function save(Module $module): bool
    {
        if ($module->id) {
            $sql = "UPDATE modules SET name=?, description=?, menu_entry=?, status=? WHERE id=?";
            $this->query($sql, $module->name, $module->description, $module->menu_entry, $module->status, $module->id);
        } else {
            $nextId = $this->queryOne("SELECT nextval('modules_id_seq') AS id");
            $module->id = (int)$nextId;
            $sql = "INSERT INTO modules (id, name, description, menu_entry, status) VALUES (?, ?, ?, ?, ?)";
            $this->query($sql, $module->id, $module->name, $module->description, $module->menu_entry, $module->status);
        }
        return true;
    }

    public function delete($id): bool
    {
        $pdo = $this->getSQLQuery()->getPdo();
        $pdo->beginTransaction();
        try {
            $this->query("DELETE FROM modules_authorities WHERE module_id=?", $id);
            $this->query("DELETE FROM users_perms WHERE module_id=?", $id);
            $this->query("DELETE FROM modules WHERE id=?", $id);
            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
