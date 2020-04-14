<?php

class ActesNotificationsTest extends S2lowTestCase {

	/**
	 * @throws Exception
	 */
    public function testNotify(){
        $this->getObjectInstancier()->set(IActesPdf::class, new ActesPdf(SITEROOT . "public.ssl/custom/images/bandeau-s2low-190.jpg"));

        $mailer = $this->getMockBuilder("Mailer")->getMock();
        $mailer
            ->method('addRecipient')
            ->withConsecutive(['eric@sigmalis.com'],['toto@toto.fr'],['foo@foo.fr'])
            ->willReturn(true);
        $mailer
            ->method('addFile')
            ->withConsecutive(
                [$this->matchesRegularExpression('#034-000000000-20170801-20170803E-AI-1-1_0.xml$#')],
                [$this->matchesRegularExpression('#034-000000000-20170801-20170803E-AI-1-1_1.pdf$#')],
                [$this->matchesRegularExpression('#TACT--000000000--20170803-16.xml$#')],
                [$this->matchesRegularExpression('#034-000000000-20170801-20170803E-AI-1-1_0.xml$#')],
                [$this->matchesRegularExpression('#034-000000000-20170801-20170803E-AI-1-1_1.pdf$#')],
                [$this->matchesRegularExpression('#TACT--000000000--20170803-16.xml$#')]
            )
            ->willReturn(true);

        $mailerFactory = $this->getMockBuilder("MailerFactory")->getMock();
        $mailerFactory->method("getInstance")->willReturn($mailer);
        $this->getObjectInstancier()->set("MailerFactory",$mailerFactory);

        $transaction_id = $this->createTransaction(4);

        $tmpFolder = new TmpFolder();
        $tmp_folder = $tmpFolder->create();
        copy(__DIR__."/fixtures/abc-TACT--000000000--20170803-16.tar.gz",$tmp_folder."/abc-TACT--000000000--20170803-16.tar.gz");


        $this->getObjectInstancier()->set("pdf_stamp_url","");
        $this->getObjectInstancier()->set('actes_files_upload_root',$tmp_folder);
        $actesNotification = $this->getObjectInstancier()->get('ActesNotification');

        $actesNotification->sendAutomaticNotification();
        $this->assertRegExp("#Notification de la transaction $transaction_id#",$this->getLogRecords()[0]['message']);

        $tmpFolder->delete($tmp_folder);
    }

    private function createTransaction($status){
        $sql="INSERT INTO actes_envelopes(user_id,siren,department,email,file_path) VALUES(1,'123456789','034',?,?) returning ID";
        $envelope_id = $this->getSQLQuery()->queryOne($sql,'eric@sigmalis.com',"abc-TACT--000000000--20170803-16.tar.gz");

        $sql = "INSERT INTO actes_transactions(envelope_id,last_status_id,user_id,authority_id,decision_date,number,nature_code,auto_broadcasted,type,broadcast_emails,broadcast_send_sources) VALUES (?,?,?,?,?,?,?,?,?,?,1) returning ID;";
        $transaction_id = $this->getSQLQuery()->queryOne($sql,$envelope_id,$status,1,1,"2017-07-01","20170728C",3,0,'1','toto@toto.fr,foo@foo.fr');

        $actesTransactionsSQL = $this->getObjectInstancier()->get("ActesTransactionsSQL");
        $actesTransactionsSQL->updateStatus($transaction_id,4,"test");

        return $transaction_id;
    }

}