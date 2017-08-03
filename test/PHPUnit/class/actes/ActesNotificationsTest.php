<?php

class ActesNotificationsTest extends S2lowTestCase {

    public function testNotify(){

        $transaction_id = $this->createTransaction(4);

        $actesNotification = $this->getObjectInstancier()->get('ActesNotification');

        $actesNotification->sendAutomaticNotification();


    }

    private function createTransaction($status){
        $sql="INSERT INTO actes_envelopes(user_id,siren,department) VALUES(1,'123456789','034') returning ID";
        $envelope_id = $this->getSQLQuery()->queryOne($sql);

        $sql = "INSERT INTO actes_transactions(envelope_id,last_status_id,user_id,authority_id,decision_date,number,nature_code,auto_broadcasted,type) VALUES (?,?,?,?,?,?,?,?,?) returning ID;";
        $transaction_id = $this->getSQLQuery()->queryOne($sql,$envelope_id,$status,1,1,"2017-07-01","20170728C",3,0,'1');

        $actesTransactionsSQL = $this->getObjectInstancier()->get("ActesTransactionsSQL");
        $actesTransactionsSQL->updateStatus($transaction_id,4,"test");

        return $transaction_id;
    }

}