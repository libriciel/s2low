<?php

namespace S2lowLegacy\Model;

use S2lowLegacy\Lib\SQL;

class AuthorityGroupSirenSQL extends SQL
{
    public function exist($id, $siren)
    {
        $sql = "SELECT * FROM authority_group_siren WHERE authority_group_id=? AND siren=?";
        return $this->queryOne($sql, $id, $siren);
    }

    public function add($id, $siren)
    {
        if ($this->exist($id, $siren)) {
            return;
        }
        $sql = "INSERT INTO authority_group_siren(authority_group_id,siren) VALUES (?,?)";
        $this->query($sql, $id, $siren);
    }

    /**
     * @deprecated 5.1.13
     */
    public function getAvailableSiren($authority_group_id, $authority_id)
    {
        $sql = "SELECT siren FROM authority_group_siren " .
            " WHERE authority_group_id=? AND siren NOT IN (" .
            "SELECT siren FROM authorities WHERE siren IS NOT NULL AND authority_group_id=? AND NOT id=?)" .
            " ORDER BY siren";
        return $this->queryOneCol($sql, $authority_group_id, $authority_group_id, $authority_id);
    }

    public function getAvailableSirenForAllGroups(?int $authorityId): array
    {
        $sql = <<<SQL
SELECT ags.authority_group_id, ags.siren
FROM authority_group_siren ags
WHERE NOT EXISTS (
    SELECT 1 FROM authorities a
    WHERE a.authority_group_id = ags.authority_group_id
      AND a.siren = ags.siren
      AND a.id != ?
)
ORDER BY ags.authority_group_id, ags.siren
SQL;

        return $this->query($sql, $authorityId);
    }
}
