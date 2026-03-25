<?php

namespace App\Repository;

use PDO;

class PesRepository extends AbstractRepository
{
    /**
     * @param int $lastId
     * @param int $limit
     * @return array Returns array of ['id' => int, 'sha1' => string, 'filename' => string, 'siren' => string]
     */
    public function getBatch(int $lastId, int $limit, ?string $minDate = null): array
    {
        $sql = "SELECT id, sha1, filename, siren, submission_date FROM helios_transactions 
                WHERE id > ? AND is_in_cloud = FALSE AND not_available = FALSE ";
        $params = [$lastId];

        if ($minDate) {
            $sql .= "AND submission_date >= ? ";
            $params[] = $minDate;
        }

        $sql .= "ORDER BY id ASC LIMIT ?";
        $params[] = $limit;

        $stmt = $this->connexion->getPDO()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
