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
    public function getBatch(int $lastId, int $limit, ?string $minDate = null, ?string $maxDate = null): array
    {
        $sql = "SELECT id, file_path, siren, submission_date FROM actes_envelopes 
                WHERE id > ? AND not_available = FALSE ";
        $params = [$lastId];

        if ($minDate) {
            $sql .= "AND submission_date >= ? ";
            $params[] = $minDate;
        }

        if ($maxDate) {
            $sql .= "AND submission_date < ? ";
            $params[] = $maxDate;
        }

        $sql .= "ORDER BY id ASC LIMIT ?";
        $params[] = $limit;
        $stmt = $this->connexion->getPDO()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
