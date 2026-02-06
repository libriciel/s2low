<?php

namespace App\Repository;

use PDO;

class HeliosRepository extends AbstractRepository
{
    /**
     * @param int $lastId
     * @param int $limit
     * @return array Returns array of ['id' => int, 'sha1' => string, 'filename' => string, 'siren' => string]
     */
    public function getBatch(int $lastId, int $limit): array
    {
        $sql = "SELECT id, sha1, filename, siren FROM helios_transactions 
                WHERE id > ? AND is_in_cloud = FALSE AND not_available = FALSE 
                ORDER BY id ASC LIMIT ?";
        $stmt = $this->connexion->getPDO()->prepare($sql);
        $stmt->execute([$lastId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAcquitBatch(int $lastId, int $limit): array
    {
        $sql = "SELECT id, acquit_filename, siren FROM helios_transactions 
                WHERE id > ? AND pes_acquit_is_in_cloud = FALSE AND pes_acquit_not_available = FALSE AND acquit_filename IS NOT NULL
                ORDER BY id ASC LIMIT ?";
        $stmt = $this->connexion->getPDO()->prepare($sql);
        $stmt->execute([$lastId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Helper to get sha1 for acquit logic (which usually uses sha1 of transaction for filename matching, or acquit_filename itself)
    // S2low logic: acquit file is stored often with sha1 of the acquit file OR related transaction logic.
    // Based on HeliosTransactionsSQL: setAcquitFilename stores the filename.
    // "getCloudId" uses sha1.
    // For ACQUIT migration, we might need specific logic if stored differently.
}
