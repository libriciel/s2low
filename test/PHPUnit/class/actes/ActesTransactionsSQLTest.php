<?php

class ActesTransactionsSQLTest extends S2lowTestCase {

    public function testGetLastArchiveFromStatus(){

        $transaction_id =$this->createTransaction('14');

        /** @var ActesTransactionsSQL $actesTransactionsSQL */
        $actesTransactionsSQL = $this->getObjectInstancier()->get("ActesTransactionsSQL");

        $actesTransactionsSQL->updateStatus($transaction_id,12,"test");

        $result_1 = $actesTransactionsSQL->getArchiveFromStatus(12);
        $this->assertNotEmpty($result_1);

        $result = $actesTransactionsSQL->getLastArchiveFromStatus(12,date("Y-m-d"));
        $this->assertEquals($result_1,$result);

    }

    private function createTransaction($status){
        $sql="INSERT INTO actes_envelopes(user_id) VALUES(1) returning ID";
        $envelope_id = $this->getSQLQuery()->queryOne($sql);

        $sql = "INSERT INTO actes_transactions(envelope_id,last_status_id,user_id,authority_id) VALUES (?,?,?,?) returning ID;";
        $transaction_id = $this->getSQLQuery()->queryOne($sql,$envelope_id,$status,1,1);
        $authoritySQL = new AuthoritySQL($this->getSQLQuery());
        $authoritySQL->updateSAE(1,array('pastell_url'=>'test','pastell_login'=>'test','pastell_password'=>'test','pastell_id_e'=>'12'));

        return $transaction_id;
    }

}