<?php

namespace App\Repository;

use PDO;

class PesRetourRepository extends AbstractRepository
{
    /**
     * @param int $lastId
     * @param int $limit
     * @return array Returns array of ['id' => int, 'sha1' => string, 'filename' => string, 'siren' => string]
     */
    public function getBatch(int $lastId, int $limit, ?string $minDate = null, ?string $maxDate = null): array
    {
        $sql = "SELECT id, authority_id, filename, date FROM helios_retour 
                WHERE id > ? AND is_in_cloud = FALSE AND not_available = FALSE ";
        $params = [$lastId];

        if ($minDate) {
            $sql .= "AND date >= ? ";
            $params[] = $minDate;
        }

        if ($maxDate) {
            $sql .= "AND date < ? ";
            $params[] = $maxDate;
        }

        $sql .= "ORDER BY id ASC LIMIT ?";
        $params[] = $limit;

        $stmt = $this->connexion->getPDO()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
