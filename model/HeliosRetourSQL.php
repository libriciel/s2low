<?php

namespace S2lowLegacy\Model;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use S2low\Exceptions\TransactionNotFoundException;
use S2low\Services\FileDataProvider;
use S2lowLegacy\Lib\SQL;
use S2lowLegacy\Lib\SQLQuery;

class HeliosRetourSQL extends SQL implements FileDataProvider
{
    public const STATUS_NON_LU = 0;
    public const STATUS_LU = 1;

    public function __construct(
        SQLQuery $sqlQuery,
        private readonly Connection $connection
    ) {
        parent::__construct($sqlQuery);
    }

    public function add($authority_id, $siret, $filename, int $size, string $sha1)
    {
        $siren = mb_substr($siret, 0, 9);
        $sql = "INSERT INTO helios_retour(authority_id, siren, filename, status, date,siret,file_size,sha1) " .
                " VALUES ( ?,?,?,0,now(),?,?,?) returning id";
        return $this->queryOne($sql, $authority_id, $siren, $filename, $siret, $size, $sha1);
    }

    public function getInfo($helios_retour_id)
    {
        $sql = "SELECT * FROM helios_retour WHERE id=?";
        return $this->queryOne($sql, $helios_retour_id);
    }

    public function getInfoFromFilename($authority_id, $filename)
    {
        $sql = "SELECT * FROM helios_retour " .
                " WHERE authority_id = ? AND filename = ?";
        return $this->queryOne($sql, $authority_id, $filename);
    }

    public function getList($authority_id)
    {
        $sql = "SELECT * FROM helios_retour WHERE status = ? AND authority_id = ? ";
        return $this->query($sql, self::STATUS_NON_LU, $authority_id);
    }

    public function changeStatus($id, $status): void
    {
        $sql = "UPDATE helios_retour SET status = ? WHERE id = ?";
        $this->query($sql, $status, $id);
    }

    /**
     * @param string[] $ids
     * @param int<0, 1> $status
     * @return void
     * @throws Exception
     */
    public function changeBulkStatus(array $ids, int $status): void
    {
        $sql = "UPDATE helios_retour SET status = :status WHERE id IN (:ids)";

        $this->connection->executeQuery(
            $sql,
            [
                'status' => $status,
                'ids' => $ids
            ],
            [
                'status' => ParameterType::INTEGER,
                'ids' => ArrayParameterType::STRING
            ]
        );
    }

    public function getAllIdPESRetourToSendInCloud()
    {
        $sql = "SELECT id FROM helios_retour WHERE is_in_cloud=FALSE ORDER BY id";
        return $this->queryOneCol($sql);
    }

    public function getAllPESRetour()
    {
        $sql = "SELECT id,is_in_cloud,not_available FROM helios_retour";
        return $this->query($sql);
    }

    public function setSize(int $id, int $filesize, string $hash)
    {
        $sql = "UPDATE helios_retour SET file_size= ?, sha1 = ? WHERE id=?";
        $this->query($sql, $filesize, $hash, $id);
    }

    public function getFilename($helios_retour_id)
    {
        $sql = "SELECT filename FROM helios_retour WHERE id=?";
        return $this->queryOne($sql, $helios_retour_id);
    }

    public function setPesRetourNotAvailable(int $helios_retour_id)
    {
        $sql = "UPDATE helios_retour SET not_available=? WHERE id=?";
        $this->query($sql, true, $helios_retour_id);
    }

    public function setPesRetourInCloud(int $helios_retour_id, bool $inCloud = true): void
    {
        $sql = "UPDATE helios_retour SET is_in_cloud=? WHERE id=?";
        $this->query($sql, intval($inCloud), $helios_retour_id);
    }

    public function getByFilename(string $filename)
    {
        $sql = "SELECT id FROM helios_retour WHERE filename=?";
        return $this->queryOne($sql, $filename);
    }

    public function isAvailable(int $object_id): bool
    {
        $sql = "SELECT not_available FROM helios_retour WHERE id=?";
        return ! $this->queryOne($sql, $object_id);
    }

    public function setAvailable(int $object_id, bool $available)
    {
        $sql = "UPDATE helios_retour SET not_available=? WHERE id=?";
        $this->query($sql, intval(! $available), $object_id);
    }

    public function isInCloud(int $object_id)
    {
        $sql = "SELECT is_in_cloud FROM helios_retour WHERE id=?";
        return $this->queryOne($sql, $object_id);
    }

    public function getRelativePath(string $transactionId): string
    {
        $heliosTransaction = $this->getInfo($transactionId);

        if ($heliosTransaction === false) {
            throw new TransactionNotFoundException('Aucune transaction Helios ne corresponds à l\'identifiant : [' . $transactionId . ']');
        }

        return $heliosTransaction['filename'];
    }

    public function getCloudId(string $transactionId): string
    {
        $heliosTransaction = $this->getInfo($transactionId);

        if ($heliosTransaction === false) {
            throw new TransactionNotFoundException('Aucune transaction Helios ne corresponds à l\'identifiant : [' . $transactionId . ']');
        }

        return $heliosTransaction['siren'] . '/' . $heliosTransaction['filename'];
    }

    public function getTransactionIdFromFileName(string $filePath): ?string
    {
        $fileName = basename($filePath);
        $transactionId = $this->queryOne("SELECT id FROM helios_retour WHERE filename LIKE ?", '%' . $fileName . '%');

        if ($transactionId === false) {
            $transactionId = null;
        }

        return $transactionId;
    }

    public function setTransactionIsInCloud(string $transactionId): void
    {
        $this->setPesRetourInCloud($transactionId);
    }
}
