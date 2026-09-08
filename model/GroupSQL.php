<?php

namespace S2lowLegacy\Model;

use S2lowLegacy\Lib\SQL;

class GroupSQL extends SQL
{
    public function getInfo($id)
    {
        $sql = "SELECT * FROM authority_groups WHERE id=?";
        return $this->queryOne($sql, $id);
    }

    public function getAll()
    {
        $sql = "SELECT * FROM authority_groups ORDER BY authority_groups.name ASC";
        return $this->query($sql);
    }

    public function edit($id, $name, $status)
    {
        if ($id) {
            $sql = "UPDATE authority_groups SET name=?, status=? WHERE id=?";
            $this->query($sql, $name, $status, $id);
        } else {
            $sql = "INSERT INTO authority_groups(id,name,status) VALUES (nextval('authority_groups_id_seq'),?,?) RETURNING id";
            $id = $this->queryOne($sql, $name, $status);
        }
        return $id;
    }

    public function groupNameAlreadyExists($id, $name)
    {
        $sql = "SELECT id FROM authority_groups WHERE name= ? ";
        $id_from_database = $this->queryOne($sql, $name);

        if (! $id_from_database) {
            return false;
        }

        if ($id) {
            return ($id != $id_from_database);
        }

        return true;
    }

    public function getGroupsIdName()
    {
        $result = [];
        $sql = "SELECT authority_groups.id, authority_groups.name FROM authority_groups ORDER BY authority_groups.name ASC";
        foreach ($this->query($sql) as $line) {
            $result[$line['id']] = $line['name'];
        }
        return $result;
    }

    /**
     * Les groupes qu'une collectivité peut désigner : actifs, et détenant le SIREN qu'elle porte —
     * un groupe à qui ce SIREN n'est pas réservé n'a pas le droit de l'administrer. Un SIREN vide
     * est une création, aucun n'est encore choisi.
     *
     * S'y ajoutent les groupes déjà désignés : désactivé ou dépossédé du SIREN après coup, un groupe
     * doit rester affiché, sans quoi l'enregistrement changerait sa désignation en silence.
     *
     * @param int[] $alreadyDesignatedGroupIds
     * @return array<int, string>
     */
    public function getSelectableGroupsIdName(string $siren = '', array $alreadyDesignatedGroupIds = []): array
    {
        $selectable = "status = 1";
        $params = [];

        if ($siren !== '') {
            $selectable .= " AND EXISTS (SELECT 1 FROM authority_group_siren ags" .
                " WHERE ags.authority_group_id = authority_groups.id AND ags.siren = ?)";
            $params[] = $siren;
        }

        $where = "($selectable)";

        if ($alreadyDesignatedGroupIds !== []) {
            $placeholders = implode(',', array_fill(0, count($alreadyDesignatedGroupIds), '?'));
            $where .= " OR id IN ($placeholders)";
            $params = [...$params, ...array_values($alreadyDesignatedGroupIds)];
        }

        $result = [];
        foreach ($this->query("SELECT id, name FROM authority_groups WHERE $where ORDER BY name ASC", $params) as $line) {
            $result[(int)$line['id']] = $line['name'];
        }
        return $result;
    }

    public function isActive(int $groupId): bool
    {
        return (int)$this->queryOne("SELECT status FROM authority_groups WHERE id = ?", [$groupId]) === 1;
    }
}
