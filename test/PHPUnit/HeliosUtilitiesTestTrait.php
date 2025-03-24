<?php

use S2lowLegacy\Lib\SQLQuery;
use S2lowLegacy\Model\HeliosTransactionsSQL;

trait HeliosUtilitiesTestTrait
{
    private function getTransactionCreationSQL($authority_id = 1, $date = null): int
    {
        if (is_null($date)) {
            $sql = "INSERT INTO helios_transactions(user_id,authority_id,last_status_id,filename,sha1,file_size) VALUES (?,?,?,?,?,?) returning ID;";

            return $this->getSQLQuery()->queryOne($sql, 1, $authority_id, 4, "toto.txt", "ab3321d34d3fb32b52332befa534c9854fff677b", 12345678);
        }

        $sql = "INSERT INTO helios_transactions(user_id,authority_id,last_status_id,filename,sha1,submission_date,file_size) VALUES (?,?,?,?,?,?,?) returning ID;";
        return $this->getSQLQuery()->queryOne($sql, 1, $authority_id, 4, "toto.txt", "ab3321d34d3fb32b52332befa534c9854fff677b", $date, 12345678);
    }

    protected function createTransaction($authority_id = 1, int $status = null, $date = null)
    {
        $transactionId = $this->getTransactionCreationSQL($authority_id, $date);

        if (is_null($status)) {
            return $transactionId;
        }
        /** @var \S2lowLegacy\Model\HeliosTransactionsSQL $heliosTransactionsSQL */
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
     * @return SQLQuery
     */
    abstract public function getSQLQuery(): SQLQuery;

    abstract public function getHeliosTransactionsSQL(): HeliosTransactionsSQL;
}
