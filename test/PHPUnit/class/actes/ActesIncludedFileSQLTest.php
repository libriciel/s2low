<?php

class ActesIncludedFileSQLTest extends S2lowTestCase {

    public function testInsert(){

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
        $transaction_info = $this->getObjectInstancier()->get("ActesTransactionsSQL")->getInfo($transaction_id);
        $this->getObjectInstancier()->get("ActesIncludedFileSQL")->addIncludedFile(
            $transaction_info['envelope_id'],
            $transaction_id,
            "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
            42,
            "toto.xml"
        );

        $all = $this->getObjectInstancier()->get("ActesIncludedFileSQL")->getAll($transaction_id);
        $this->assertEquals("toto.xml",$all[0]['posted_filename']);
    }


}