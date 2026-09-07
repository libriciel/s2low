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
     * Les SIREN réservés au groupe et qu'aucune autre collectivité n'utilise déjà.
     *
     * @return string[]
     */
    public function getAvailableSiren(int $authorityGroupId, int $authorityId): array
    {
        $sql = "SELECT siren FROM authority_group_siren " .
            " WHERE authority_group_id=? AND siren NOT IN (" .
            "SELECT siren FROM authorities WHERE siren IS NOT NULL AND NOT id=?)" .
            " ORDER BY siren";
        return $this->queryOneCol($sql, $authorityGroupId, $authorityId);
    }

    /**
     * Les SIREN réservés à chaque groupe et qu'aucune autre collectivité n'utilise déjà.
     *
     * Un SIREN identifie une collectivité et une seule — l'unicité est vérifiée sans regarder le
     * groupe. La réservation par un groupe dit qui a le droit de le poser, pas qu'il soit libre :
     * restreindre l'exclusion aux collectivités du groupe revenait à proposer un SIREN que
     * l'enregistrement refuse.
     */
    public function getAvailableSirenForAllGroups(?int $authorityId): array
    {
        $sql = <<<SQL
SELECT ags.authority_group_id, ags.siren
FROM authority_group_siren ags
WHERE NOT EXISTS (
    SELECT 1 FROM authorities a
    WHERE a.siren = ags.siren
      AND a.id != ?
)
ORDER BY ags.authority_group_id, ags.siren
SQL;

        return $this->query($sql, $authorityId);
    }
}
