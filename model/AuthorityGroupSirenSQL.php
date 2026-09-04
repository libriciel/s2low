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

    /**
     * Les SIREN que tous les groupes administrateurs autorisent — leur intersection — et qu'aucune
     * autre collectivité n'utilise déjà.
     *
     * Un SIREN identifie une collectivité et une seule : la réservation par un groupe ne dit que
     * qui a le droit de le poser, pas qu'il soit libre. Restreindre l'exclusion aux collectivités
     * du groupe reviendrait à proposer un SIREN que l'enregistrement refusera.
     *
     * @param int[] $groupIds
     * @return string[]
     */
    public function getAvailableSirenForGroups(array $groupIds, int $authorityId): array
    {
        $groupIds = array_values(array_unique($groupIds));

        if ($groupIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($groupIds), '?'));

        $sql = <<<SQL
SELECT ags.siren
FROM authority_group_siren ags
WHERE ags.authority_group_id IN ($placeholders)
  AND NOT EXISTS (
    SELECT 1 FROM authorities a
    WHERE a.siren = ags.siren
      AND a.id != ?
)
GROUP BY ags.siren
HAVING COUNT(DISTINCT ags.authority_group_id) = ?
ORDER BY ags.siren
SQL;

        return $this->queryOneCol($sql, [...$groupIds, $authorityId, count($groupIds)]);
    }
}
