<?php

declare(strict_types=1);

namespace PHPUnit;

use Exception;
use S2lowLegacy\Class\actes\ActesEnvelopeSQL;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Lib\SQLQuery;

/**
 * Created by PhpStorm.
 * User: eric
 * Date: 21/08/2018
 * Time: 11:41
 */

trait ActesUtilitiesTestTrait
{
    /**
     * @throws Exception
     */
    protected function createTransaction(int $status, string $archive_path = '', ?string $date = '2017-07-01'): int
    {
        $sql = "INSERT INTO actes_envelopes(user_id,siren,department) VALUES(1,'000000000','034') returning ID";
        $envelope_id = $this->getSQLQuery()->queryOne($sql);

        $sql = 'INSERT INTO actes_transactions(
                               envelope_id,
                               last_status_id,
                               user_id,
                               authority_id,
                               decision_date,
                               number,
                               nature_code,
                               type,
                               classification
                               ) VALUES (?,?,?,?,?,?,?,?,?) returning ID;';
        $transaction_id = $this->getSQLQuery()->queryOne(
            $sql,
            $envelope_id,
            $status,
            1,
            1,
            $date,
            '20170728C',
            3,
            1,
            '1.1.1'
        );

        $flux_retour = '';
        if ($status === ActesStatusSQL::STATUS_ACQUITTEMENT_RECU) {
            $flux_retour = 'Acquittement très officiel';
        }
        $this->getActesTransactionsSQL()->updateStatus($transaction_id, $status, '', $flux_retour, $date);

        if ($archive_path) {
            $relative_path = basename($archive_path);
            $destination = $this->getObjectInstancier()->get('actes_files_upload_root') . '/' . basename($archive_path);
            copy($archive_path, $destination);
            $sql = 'UPDATE actes_envelopes SET file_path=?,file_size=? WHERE id=?';
            $this->getSQLQuery()->query($sql, $relative_path, filesize($archive_path), $envelope_id);
        }

        $unique_id = $this->getActesTransactionsSQL()->guessUniqueId($transaction_id);

        $sql = 'UPDATE actes_transactions SET unique_id=? WHERE id=?';
        $this->getSQLQuery()->query($sql, $unique_id, $transaction_id);

        $this->createActeIncludedFiles($transaction_id, $envelope_id);

        return $transaction_id;
    }

    private function createActeIncludedFiles($transactionId, $envelopeId)
    {
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
            $envelopeId,
            $transactionId,
            "PDFTest.pdf",
            "application/pdf",
            10407,
            "PDFTest.pdf",
            "7c839d1ba8f47aee14839d087d7dd67f68c36778",
            "99_AI"
        );
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
        $actesEnvelopeSQL = $this->getObjectInstancier()->get(ActesEnvelopeSQL::class);

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

    protected function updateStatus(int $transaction_id, int $status_id, ?string $message, string $date = null): void
    {
        $this->getActesTransactionsSQL()->updateStatus($transaction_id, $status_id, $message, '', $date);
    }
    abstract protected function getActesTransactionsSQL(): ActesTransactionsSQL;

    abstract public function getSQLQuery(): SQLQuery;
}
