<?php

namespace S2lowLegacy\Class\mailsec;

use phpseclib3\Exception\FileNotFoundException;
use S2low\Exceptions\TransactionNotFoundException;
use S2low\Services\FileDataProvider;
use S2lowLegacy\Lib\SQL;

class MailTransactionSQL extends SQL implements FileDataProvider
{
    public function getTransactionIdToSendInCloud()
    {
        $sql = "SELECT id FROM mail_transaction WHERE fn_download IS NOT NULL AND is_in_cloud=FALSE AND not_available=FALSE ORDER BY id";
        return $this->queryOneCol($sql);
    }

    public function getFnDownload(int $mail_transaction_id)
    {
        $sql = "SELECT fn_download FROM mail_transaction WHERE id=?";
        return $this->queryOne($sql, $mail_transaction_id);
    }

    public function setNotAvailable(int $mail_transaction_id)
    {
        $sql = "UPDATE mail_transaction SET not_available=? WHERE id=?";
        $this->query($sql, true, $mail_transaction_id);
    }

    public function setInCloud(int $mail_transaction_id, $inCloud = true): void
    {
        $sql = "UPDATE mail_transaction SET is_in_cloud=? WHERE id=?";
        $this->query($sql, intval($inCloud), $mail_transaction_id);
    }

    public function fileExists(string $fn_download, string $filename)
    {
        $sql = "SELECT count(*) FROM mail_transaction " .
            " JOIN mail_included_file ON mail_transaction.id=mail_included_file.mail_transaction_id " .
            " WHERE fn_download=? AND filename=?";
        return boolval($this->queryOne($sql, $fn_download, $filename));
    }

    public function fnDownloadExists($fn_download)
    {
        $sql = "SELECT count(*) FROM mail_transaction WHERE fn_download=? ";
        return boolval($this->queryOne($sql, $fn_download));
    }

    public function getIdFromFnDownload($fn_download)
    {
        $sql = "SELECT id FROM mail_transaction WHERE fn_download=? ";
        return $this->queryOne($sql, $fn_download);
    }

    public function getIdByFilename(string $fn_download)
    {
        $sql = "SELECT id FROM mail_transaction WHERE fn_download=?";
        return $this->queryOne($sql, $fn_download);
    }

    public function isAvailable(int $object_id): bool
    {
        $sql = "SELECT not_available FROM mail_transaction WHERE id=?";
        return ! $this->queryOne($sql, $object_id);
    }

    public function setAvailable(int $object_id, bool $available)
    {
        $sql = "UPDATE mail_transaction SET not_available=? WHERE id=?";
        $this->query($sql, intval(! $available), $object_id);
    }

    public function isInCloud(int $object_id)
    {
        $sql = "SELECT is_in_cloud FROM mail_transaction WHERE id=?";
        return ! $this->queryOne($sql, $object_id);
    }

    public function getRelativePath(string $transactionId): string
    {
        $transaction = $this->queryOne("SELECT id, fn_download FROM mail_transaction WHERE id=?", $transactionId);

        if ($transaction === false) {
            throw new TransactionNotFoundException('Aucune transaction Mail ne correspond à l\'identifiant : [' . $transactionId . '].');
        }

        return $transaction['fn_download'] . '/mail.zip';
    }

    public function getCloudId(string $transactionId): string
    {
        $transaction = $this->queryOne(
            "SELECT 
                        mt.id, 
                        mt.fn_download, 
                        a.siren
                    FROM mail_transaction mt
                    INNER JOIN users u ON u.id = mt.user_id
                    INNER JOIN authorities a ON a.id = u.authority_id
                    WHERE mt.id = ?;
                   ",
            $transactionId
        );

        if ($transaction === false) {
            throw new TransactionNotFoundException('Aucune transaction Mail ne correspond à l\'identifiant : [' . $transactionId . '].');
        }

        return $transaction['siren'] . '/' . $transaction['fn_download'];
    }

    public function getTransactionIdFromFileName(string $filePath): string
    {
        // TODO: Implement getTransactionIdFromFileName() method.
        return '';
    }
}
