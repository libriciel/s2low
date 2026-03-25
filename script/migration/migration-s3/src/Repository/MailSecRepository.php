<?php

namespace App\Repository;

use PDO;

class MailSecRepository extends AbstractRepository
{
    /**
     * @param int $lastId
     * @param int $limit
     * @return array Returns array of ['id' => int, 'fn_download' => string, 'siren' => string]
     */
    public function getBatch(int $lastId, int $limit, ?string $minDate = null): array
    {
        $sql = "SELECT mt.id, mt.fn_download, mt.date_envoi, a.siren 
                FROM mail_transaction mt
                JOIN users u ON u.id = mt.user_id
                JOIN authorities a ON a.id = u.authority_id
                WHERE mt.id > ? AND mt.is_in_cloud = FALSE AND mt.not_available = FALSE ";
        $params = [$lastId];

        if ($minDate) {
            $sql .= "AND mt.date_envoi >= ? ";
            $params[] = $minDate;
        }

        $sql .= "ORDER BY mt.id ASC LIMIT ?";
        $params[] = $limit;

        $stmt = $this->connexion->getPDO()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
//                WHERE mt.id > ? AND mt.is_in_cloud = FALSE AND mt.not_available = FALSE AND mt.fn_download IS NOT NULL
    }
}
