<?php

namespace App\Repository;

use PDO;

class PesAcquitRepository extends AbstractRepository
{
    public function getBatch(int $lastId, int $limit): array
    {
        $sql = "SELECT id, acquit_filename, siren, submission_date FROM helios_transactions 
                WHERE id > ? AND pes_acquit_is_in_cloud = FALSE AND pes_acquit_not_available = FALSE AND acquit_filename IS NOT NULL
                ORDER BY id ASC LIMIT ?";
        $stmt = $this->connexion->getPDO()->prepare($sql);
        $stmt->execute([$lastId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
