<?php

namespace App\Repository;

use PDO;

class ActesRepository extends AbstractRepository
{
    /**
     * @param int $lastId
     * @param int $limit
     * @return array Returns array of ['id' => int, 'file_path' => string, 'siren' => string]
     */
    public function getBatch(int $lastId, int $limit): array
    {
        $sql = "SELECT id, file_path, siren, submission_date FROM actes_envelopes 
                WHERE id > ? AND is_in_cloud = FALSE AND not_available = FALSE 
                ORDER BY id ASC LIMIT ?";
        $stmt = $this->connexion->getPDO()->prepare($sql);
        $stmt->execute([$lastId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
