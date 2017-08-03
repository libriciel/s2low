<?php

class ActesTransactionsSQLTest extends S2lowTestCase {

    private function getActesTransactionsSQL(){
        return $this->getObjectInstancier()->get("ActesTransactionsSQL");
    }

    public function testGetLastArchiveFromStatus(){
        $transaction_id =$this->createTransaction('14');
        $this->getActesTransactionsSQL()->updateStatus($transaction_id,12,"test");

        $result_1 = $this->getActesTransactionsSQL()->getArchiveFromStatusWithSAE(12);
        $this->assertNotEmpty($result_1);

        $result = $this->getActesTransactionsSQL()->getLastArchiveFromStatus(12,date("Y-m-d"));
        $this->assertEquals($result_1,$result);

    }

    private function createTransaction($status){
        $sql="INSERT INTO actes_envelopes(user_id,siren,department) VALUES(1,'000000000','034') returning ID";
        $envelope_id = $this->getSQLQuery()->queryOne($sql);

        $sql = "INSERT INTO actes_transactions(envelope_id,last_status_id,user_id,authority_id,decision_date,number,nature_code) VALUES (?,?,?,?,?,?,?) returning ID;";
        $transaction_id = $this->getSQLQuery()->queryOne($sql,$envelope_id,$status,1,1,"2017-07-01","20170728C",3);
        $authoritySQL = new AuthoritySQL($this->getSQLQuery());
        $authoritySQL->updateSAE(1,array('pastell_url'=>'test','pastell_login'=>'test','pastell_password'=>'test','pastell_id_e'=>'12'));

        return $transaction_id;
    }

    public function testGetNbByStatus(){
        $this->createTransaction(ActesStatusSQL::STATUS_POSTE);
        $this->assertEquals(1,$this->getActesTransactionsSQL()->getNbByStatus(ActesStatusSQL::STATUS_POSTE));
    }

    public function testCreateRelatedTransaction(){
        $transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU);
        $transaction_info = $this->getActesTransactionsSQL()->getInfo($transaction_id);
        $related_transaction_id = $this->getActesTransactionsSQL()->createRelatedTransaction(
            $transaction_info['envelope_id'],
            2,
            '2017-07-25',
            $transaction_id
        );
        $this->assertNotNull($related_transaction_id);
        $transaction_info = $this->getActesTransactionsSQL()->getInfo($related_transaction_id);
        $this->assertEquals($transaction_id,$transaction_info['related_transaction_id']);

    }

    public function testGuessUniqueId(){
        $transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU);

        $unique_id = $this->getActesTransactionsSQL()->guessUniqueId($transaction_id);

        $this->assertEquals("034-000000000-20170701-20170728C-AI",$unique_id);

    }

}