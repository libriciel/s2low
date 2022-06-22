<?php

use PHPUnit\Framework\MockObject\MockObject;

class ActesNotificationsTest extends S2lowTestCase
{
    /**
     * @var MockObject|Mailer
     */
    private $mailer;
    /** @var int  */
    private $transaction_id;
    /** @var TmpFolder */
    private $tmpFolder;
    /** @var string */
    private $tmpFolderPath;
    /**
     * @var ActesNotification|object|ObjectInstancier
     */
    private $actesNotification;

    public function setUp(): void
    {
        parent::setUp();
        $this->getObjectInstancier()->set(IActesPdf::class, new ActesPdf(SITEROOT . "public.ssl/custom/images/bandeau-s2low-190.jpg"));
        $this->mailer = $this->getMockBuilder("Mailer")->getMock();
        $mailerFactory = $this->getMockBuilder("MailerFactory")->getMock();
        $mailerFactory->method("getInstance")->willReturn($this->mailer);
        $this->getObjectInstancier()->set("MailerFactory", $mailerFactory);

        $this->transaction_id = $this->createTransaction(4);

        $this->tmpFolder = new TmpFolder();
        $this->tmpFolderPath = $this->tmpFolder->create();

        $this->getObjectInstancier()->set("pdf_stamp_url", "");
        $this->getObjectInstancier()->set('actes_files_upload_root', $this->tmpFolderPath);
        $this->actesNotification = $this->getObjectInstancier()->get('ActesNotification');
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->tmpFolder->delete($this->tmpFolderPath);
    }

    /**
     * @throws Exception
     */
    public function testNotify()
    {
        copy(__DIR__ . "/fixtures/abc-TACT--000000000--20170803-16.tar.gz", $this->tmpFolderPath . "/abc-TACT--000000000--20170803-16.tar.gz");

        $this->mailer
            ->method('addRecipient')
            ->withConsecutive(['eric@sigmalis.com'], ['toto@toto.fr'], ['foo@foo.fr'])
            ->willReturn(true);
        $this->mailer
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

        $this->actesNotification->sendAutomaticNotification();
        $this->assertMatchesRegularExpression("#Notification de la transaction $this->transaction_id#", $this->getLogRecords()[0]['message']);
    }

    private function createTransaction($status): int
    {
        $sql = "INSERT INTO actes_envelopes(user_id,siren,department,email,file_path) VALUES(1,'123456789','034',?,?) returning ID";
        $envelope_id = $this->getSQLQuery()->queryOne($sql, 'eric@sigmalis.com', "abc-TACT--000000000--20170803-16.tar.gz");

        $sql = "INSERT INTO actes_transactions(envelope_id,last_status_id,user_id,authority_id,decision_date,number,nature_code,auto_broadcasted,type,broadcast_emails,broadcast_send_sources) VALUES (?,?,?,?,?,?,?,?,?,?,1) returning ID;";
        $transaction_id = $this->getSQLQuery()->queryOne($sql, $envelope_id, $status, 1, 1, "2017-07-01", "20170728C", 3, 0, '1', 'toto@toto.fr,foo@foo.fr');

        $actesTransactionsSQL = $this->getObjectInstancier()->get("ActesTransactionsSQL");
        $actesTransactionsSQL->updateStatus($transaction_id, 4, "test");

        return $transaction_id;
    }

    /**
     * @throws Exception
     */
    public function testNotifyWithWrongZipWillSendMailAnyway()
    {
        copy(__DIR__ . "/fixtures/convention-exemple.pdf", $this->tmpFolderPath . "/abc-TACT--000000000--20170803-16.tar.gz");
        $this->mailer->expects($this->exactly(3))->method('sendMail');

        $this->actesNotification->sendAutomaticNotification();
    }

    /**
     * @throws Exception
     */
    public function testNotifyWithWrongZipWillnotAddFiles()
    {
        copy(__DIR__ . "/fixtures/convention-exemple.pdf", $this->tmpFolderPath . "/abc-TACT--000000000--20170803-16.tar.gz");
        $this->mailer->expects($this->never())->method('addFile');

        $this->actesNotification->sendAutomaticNotification();
    }

    /**
     * @throws Exception
     */
    public function testNotifyWithWrongZipWillLogErrors()
    {
        copy(__DIR__ . "/fixtures/convention-exemple.pdf", $this->tmpFolderPath . "/abc-TACT--000000000--20170803-16.tar.gz");
        $this->mailer->expects($this->never())->method('addFile');

        $this->actesNotification->sendAutomaticNotification();

        $logRecords = $this->getLogRecords();
        $this->assertMatchesRegularExpression(
            "#Erreur lors de la décompression#",
            $logRecords[2]["message"]
        );
    }
}
