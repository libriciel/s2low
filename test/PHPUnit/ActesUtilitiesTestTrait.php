<?php

declare(strict_types=1);

namespace PHPUnit;

use Exception;
use S2lowLegacy\Class\actes\ActesEnvelopeSQL;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\Database;
use S2lowLegacy\Lib\SQLQuery;

/**
 * Created by PhpStorm.
 * User: eric
 * Date: 21/08/2018
 * Time: 11:41
 */
trait ActesUtilitiesTestTrait
{
    public function createTransactionWithStatus(int $status, ?string $archive_path)
    {
        return $this->createTransactionOfType(
            status: $status,
            archivePath: $archive_path,
        );
    }

    protected function createTransactionOfType(
        int $status,
        int $type = 1,
        string $archivePath = '',
        ?string $date = '2017-07-01',
        ?string $submissionDate = '2017-07-01',
        int $userId = 13
    ): int {
        $sql = "INSERT INTO actes_envelopes(user_id,siren,department, submission_date) VALUES(13,'000000000','034', ?) returning ID";
        $envelope_id = self::getContainer()->get(Database::class)->getOneValue($sql, [$submissionDate]);
        $sql = 'INSERT INTO actes_transactions(
                               envelope_id,
                               last_status_id,
                               user_id,
                               authority_id,
                               decision_date,
                               number,
                               nature_code,
                               type,
                               classification,
                               classification_date
                               ) VALUES (?,?,?,?,?,?,?,?,?,?) returning ID;';
        $transaction_id = self::getContainer()->get(Database::class)->getOneValue(
            $sql,
            $envelope_id,
            $status,
            $userId,
            1,
            $date,
            '20170728C',
            3,
            $type,
            '1.1.1',
            '2015-08-28'
        );

        $flux_retour = '';
        if ($status === ActesStatusSQL::STATUS_ACQUITTEMENT_RECU) {
            $flux_retour = 'Acquittement très officiel';
        }
        $this->getActesTransactionsSQL()->updateStatus($transaction_id, $status, '', $flux_retour, $date);
        if ($archivePath) {
            $relative_path = basename($archivePath);
            $destination = self::getContainer()->getParameter('app.actes.files_upload_root') . '/' . basename(
                $archivePath
            );
            copy($archivePath, $destination);
            $sql = 'UPDATE actes_envelopes SET file_path=?,file_size=? WHERE id=?';
            self::getContainer()->get(SQLQuery::class)->query(
                $sql,
                $relative_path,
                filesize($archivePath),
                $envelope_id
            );
        }
        $unique_id = $this->getActesTransactionsSQL()->guessUniqueId($transaction_id);

        $sql = 'UPDATE actes_transactions SET unique_id=? WHERE id=?';
        self::getContainer()->get(SQLQuery::class)->query($sql, $unique_id, $transaction_id);


        return $transaction_id;
    }

    protected function updateStatus(int $transaction_id, int $status_id, ?string $message, ?string $date = null): void
    {
        $this->getActesTransactionsSQL()->updateStatus($transaction_id, $status_id, $message, '', $date);
    }

    abstract protected function getActesTransactionsSQL(): ActesTransactionsSQL;

    protected function createTransactionWithTmpDir(
        int $status,
        ?string $archive_path,
        string $tmp_dir,
        string $lastEnveloppeId,
        string $siren = '000000000'
    ) {
        if (is_null($archive_path)) {
            $archive_name = uniqid((string)rand(), true);
        } else {
            $archive_name = basename($archive_path);
            copy($archive_path, $tmp_dir . "/$archive_name");
        }

        $transaction_id = $this->getActesTransactionsSQL()->create($lastEnveloppeId, $status, 13, 1);

        $this->getActesTransactionsSQL()->updateStatus(
            $transaction_id,
            $status,
            "Création de la transaction via PHPUNIT"
        );
        return $transaction_id;
    }

    /**
     * @return int
     * @throws \Exception
     */
    protected function createRelatedTransaction(): int
    {
        $transaction_id = $this->createTransaction(4);

        $transaction_info = $this->getActesTransactionsSQL()->getInfo($transaction_id);
        /** @var ActesEnvelopeSQL $actesEnvelopeSQL */
        $actesEnvelopeSQL = self::getContainer()->get(ActesEnvelopeSQL::class);

        $related_envelope_id = $actesEnvelopeSQL->createRelatedEnveloppe(
            $transaction_info['envelope_id'],
            'a',
            12
        );

        return $this->getActesTransactionsSQL()->createRelatedTransaction(
            $related_envelope_id,
            3,
            '2018-01-01',
            $transaction_id
        );
    }

    /**
     * @throws Exception
     */
    protected function createTransaction(
        int $status,
        string $archivePath = '',
        ?string $date = '2017-07-01',
        ?string $submissionDate = '2017-07-01',
        int $userId = 13
    ): int {
        return $this->createTransactionOfType(
            $status,
            1,
            $archivePath,
            $date,
            $submissionDate,
            $userId
        );
    }

    protected function createEnveloppe($filePath, $siren = '000000000', $userId = 1)
    {
        $sql = "INSERT INTO actes_envelopes(user_id,file_path,submission_date,siren) VALUES(?,?,now(),?) RETURNING ID";
        return $this->getSQLQuery()->queryOne($sql, $userId, $filePath, $siren);
    }

    abstract public function getSQLQuery(): SQLQuery;

    protected function createFilePath($archivePath, $tmpDir): string
    {
        if (is_null($archivePath)) {
            $archive_name = uniqid((string)rand(), true);
        } else {
            $archive_name = basename($archivePath);
            copy($archivePath, $tmpDir . "/$archive_name");
        }

        return basename($tmpDir) . "/$archive_name";
    }

    private function createActeIncludedFiles($transactionId)
    {
        $sql = "SELECT id FROM actes_envelopes WHERE user_id = 13 AND siren='000000000'";
        $enveloppeId = $this->getSQLQuery()->queryOne($sql);

        $sql = "INSERT INTO actes_included_files (
            envelope_id,
            transaction_id,
            filename,
            filetype,
            filesize,
            posted_filename,
            sha1,
            code_pj
        ) VALUES (?,?,?,?,?,?,?,?)
        ";

        return $this->getSQLQuery()->queryOne(
            $sql,
            $enveloppeId,
            $transactionId,
            "99_AI-034-123456789-20250306-43636243-AI-1-1_1.pdf",
            "application/pdf",
            10407,
            "PDFTest.pdf",
            "7c839d1ba8f47aee14839d087d7dd67f68c36778",
            "99_AI"
        );
    }
}
