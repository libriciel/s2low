<?php

class ActesIncludedFileSQLTest extends S2lowTestCase
{
    public function testInsert()
    {

        $envelope_id = $this->getObjectInstancier()->get("ActesEnvelopeSQL")->create(
            1,
            "000000000/abc-EACT--210703385--20170612-2.tar.gz"
        );

        $transaction_id  = $this->getObjectInstancier()->get("ActesTransactionsSQL")->create(
            $envelope_id,
            ActesStatusSQL::STATUS_POSTE,
            1,
            1
        );
        $this->getObjectInstancier()->get("ActesTransactionsSQL")->setAntivirusCheck($transaction_id);
        $transaction_info = $this->getObjectInstancier()->get("ActesTransactionsSQL")->getInfo($transaction_id);
        $this->getObjectInstancier()->get("ActesIncludedFileSQL")->addIncludedFile(
            $transaction_info['envelope_id'],
            $transaction_id,
            "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
            42,
            "toto.xml"
        );

        $all = $this->getObjectInstancier()->get("ActesIncludedFileSQL")->getAll($transaction_id);
        $this->assertEquals("toto.xml", $all[0]['posted_filename']);
    }

    use ActesUtilitiesTestTrait;

    /**
     * @throws Exception
     */
    public function testGetSendFile()
    {

        $transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_POSTE);

        $actesTransactionSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);

        $transaction_info = $actesTransactionSQL->getInfo($transaction_id);

        $actesIncludedFileSQL = $this->getObjectInstancier()->get(ActesIncludedFileSQL::class);


        $actesIncludedFileSQL->addIncludedFile($transaction_info['envelope_id'], $transaction_id, "text/plain", 12, "test.txt");
        $actesIncludedFileSQL->addIncludedFile($transaction_info['envelope_id'], $transaction_id, "text/plain", 12, "test2.txt");

        $sql = "UPDATE actes_included_files SET sha1='aaa'";
        $this->getSQLQuery()->query($sql);

        $this->assertEquals('test2.txt', $actesIncludedFileSQL->getSendFile($transaction_id)[1]['filename']);
    }
}
