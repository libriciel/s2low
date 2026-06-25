<?php

use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Lib\SQLQuery;
use S2lowLegacy\Model\HeliosRetourSQL;
use S2lowLegacy\Model\HeliosTransactionsSQL;

trait HeliosUtilitiesTestTrait
{
    private function getTransactionCreationSQL($authority_id = 1, $date = null): int
    {
        if (is_null($date)) {
            $sql = "INSERT INTO helios_transactions(user_id,authority_id,last_status_id,filename,sha1, acquit_filename, file_size) VALUES (?,?,?,?,?, ?,?) returning ID;";

            return $this->getSQLQuery()->queryOne(
                $sql,
                13,
                $authority_id,
                4,
                "toto.txt",
                "ab3321d34d3fb32b52332befa534c9854fff677b",
                "acquit_file.xml",
                12345678
            );
        }

        $sql = "INSERT INTO helios_transactions(user_id,authority_id,last_status_id,filename,sha1,submission_date,file_size) VALUES (?,?,?,?,?,?,?) returning ID;";
        return $this->getSQLQuery()->queryOne(
            $sql,
            13,
            $authority_id,
            4,
            "toto.txt",
            "ab3321d34d3fb32b52332befa534c9854fff677b",
            $date,
            12345678
        );
    }

    protected function createTransaction($authority_id = 1, ?int $status = null, $date = null): int
    {
        $transactionId = $this->getTransactionCreationSQL($authority_id, $date);

        if (is_null($status)) {
            return $transactionId;
        }
        $this->getHeliosTransactionsSQL()->updateStatus($transactionId, $status, "test");
        return $transactionId;
    }

    protected function addPESAcquitTo($transactionId, $acquitFilename): void
    {
        $this->heliosTransactionsSQL->updateStatus(
            $transactionId,
            HeliosTransactionsSQL::ACQUITTE,
            "sample message",
        );

        $this->heliosTransactionsSQL->setAcquitFilename($transactionId, $acquitFilename);
    }

    /**
     * @param $collectiviteId
     * @param $filename
     * @return false|mixed
     */
    protected function addPESRetourToCollectivite(
        $collectiviteId,
        $filename
    ): mixed {
        $siret = '123456789';
        $size = 0;
        $sha1 = 'sha1';

        $heliosRetourSQL = LegacyObjectsManager::getObject(HeliosRetourSQL::class);
        return $heliosRetourSQL->add(
            $collectiviteId,
            $siret,
            $filename,
            $size,
            $sha1
        );
    }

    /**
     * @return SQLQuery
     */
    abstract public function getSQLQuery(): SQLQuery;

    abstract public function getHeliosTransactionsSQL(): HeliosTransactionsSQL;
}
