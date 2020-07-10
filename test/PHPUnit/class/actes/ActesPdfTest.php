<?php

require_once(__DIR__."/../../../../public.ssl/modules/actes/class/ActesTransaction.class.php");


class ActesPdfTest extends S2lowTestCase {

    public function testCreatePdf(){
        $this->getObjectInstancier()->set(IActesPdf::class, new ActesPdf(SITEROOT . "public.ssl/custom/images/bandeau-s2low-190.jpg"));

        $transaction_id = $this->createTransaction(4);

        $bordereauPdfGenerator = $this->getObjectInstancier()->get(BordereauPdfGenerator::class);

        $this->assertNotEmpty($bordereauPdfGenerator->generate($transaction_id,"test_pdf.pdf",true,"S"));
    }


    private function createTransaction($status){
        $sql="INSERT INTO actes_envelopes(user_id,siren,department) VALUES(1,'123456789','034') returning ID";
        $envelope_id = $this->getSQLQuery()->queryOne($sql);

        $sql = "INSERT INTO actes_transactions(envelope_id,last_status_id,user_id,authority_id,decision_date,number,nature_code,auto_broadcasted,type) VALUES (?,?,?,?,?,?,?,?,?) returning ID;";
        $transaction_id = $this->getSQLQuery()->queryOne($sql,$envelope_id,$status,1,1,"2017-07-01","20170728C",3,0,'1');

        return $transaction_id;
    }
}