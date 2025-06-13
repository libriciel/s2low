<?php

use Monolog\Handler\TestHandler;
use Monolog\Level;
use S2low\Services\MailActesNotifications\MailerSymfony;
use S2low\Services\MailActesNotifications\MailerSymfonyFactory;
use S2lowLegacy\Class\actes\ActesEnvelopeSQL;
use S2lowLegacy\Class\actes\ActesNotification;
use S2lowLegacy\Class\actes\ActesRetriever;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\actes\ActeTamponne;
use S2lowLegacy\Class\actes\BordereauPdfGenerator;
use S2lowLegacy\Class\S2lowLogger;
use S2lowLegacy\Class\TmpFolder;
use S2lowLegacy\Model\AuthoritySQL;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;

class ActesNotificationsTest extends S2lowTestCase
{
    private TmpFolder $tmpFolder;
    private MailerSymfony $mailer;
    private MailerSymfonyFactory $mailerFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->deleteZip();
        $this->tmpFolder = new TmpFolder();
        $this->tmpFolderPath = $this->tmpFolder->create();

        $this->mailer = $this->getMockBuilder(MailerSymfony::class)
            ->disableOriginalConstructor()->getMock();

        $this->mailerFactory = $this->getMockBuilder(MailerSymfonyFactory::class)
            ->disableOriginalConstructor()->getMock();
    }

    /**
     * This method is called after each test.
     */
    public function tearDown(): void
    {
        $this->tmpFolder->delete($this->tmpFolderPath);
        parent::tearDown();
    }

    public function testNotifyWithWrongZipWillSendMailAnyway()
    {
        $this->mailer->method("sendMailWithHtml")->willReturn(true);
        $this->mailerFactory->method("getInstance")->willReturn($this->mailer);

        $this->createTransaction();
        $actesNotification = $this->createActesNotification($this->mailerFactory);
        $projectDirectory = self::getContainer()->getParameter('kernel.project_dir');

        $this->mailer->expects($this->exactly(3))->method('sendMailWithHtml');

        $actesNotification->sendAutomaticNotification();
    }

    private function createTransaction(): int
    {
        $status = 4;
        $userId = 113;
        $authorityId = 101;

        $sql = "INSERT INTO actes_envelopes(user_id,siren,department,email,file_path) VALUES(?,'123456789','034',?,?) returning ID";
        $envelope_id = $this->getSQLQuery()->queryOne(
            $sql,
            $userId,
            'eric@sigmalis.com',
            "abc-TACT--000000000--20170803-16.tar.gz"
        );
        $sql = "INSERT INTO actes_transactions(envelope_id,last_status_id,user_id,authority_id,decision_date,number,nature_code,auto_broadcasted,type,broadcast_emails,broadcast_send_sources) VALUES (?,?,?,?,?,?,?,?,?,?,1) returning ID;";
        $transaction_id = $this->getSQLQuery()->queryOne(
            $sql,
            $envelope_id,
            $status,
            $userId,
            $authorityId,
            "2017-07-01",
            "20170728C",
            3,
            0,
            '1',
            'toto@toto.fr,foo@foo.fr'
        );
        $sql = 'INSERT INTO actes_transactions_workflow(transaction_id, status_id, date, message, flux_retour) VALUES (?,?,now(),?,?)';
        $this->getSQLQuery()->queryOne($sql, $transaction_id, $status, 'Creation', '');
        $actesTransactionsSQL = self::getContainer()->get(ActesTransactionsSQL::class);
        $actesTransactionsSQL->updateStatus($transaction_id, 4, "test");

        return $transaction_id;
    }

    private function createActesNotification(MailerSymfonyFactory $mailerFactory): ActesNotification
    {

        return new ActesNotification(
            self::getContainer()->get(ActesTransactionsSQL::class),
            self::getContainer()->get(ActeTamponne::class),
            self::getContainer()->get(AuthoritySQL::class),
            self::getContainer()->get(ActesEnvelopeSQL::class),
            $mailerFactory,
            $this->s2lowLogger,
            self::getContainer()->getParameter('app.actes_appli_trigramme'),
            self::getContainer()->get(ActesRetriever::class),
            self::getContainer()->get(BordereauPdfGenerator::class),
            self::getContainer()->get(Environment::class),
            self::getContainer()->getParameter('app.use_prod_notification'),
        );
    }

    /**
     * @throws Exception
     */
    public function testNotify()
    {
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

        $transaction_id = $this->createTransaction();

        $actesNotification = $this->createActesNotification($this->mailerFactory);
        $actesNotification->sendAutomaticNotification();

        $this->assertTrue(
            $this->testHandler->hasRecord(
                "Notification de la transaction $transaction_id",
                Level::Info
            )
        );
    }

    public function testNotifyWithWrongZipWillnotAddFiles()
    {
        $projectDirectory = self::getContainer()->getParameter('kernel.project_dir');

        $this->mailer->expects($this->never())->method('addFile');

        $actesNotification = $this->createActesNotification($this->mailerFactory);
        $actesNotification->sendAutomaticNotification();
    }

    public function testNotifyWithWrongZipWillLogErrors()
    {
        $projectDirectory = self::getContainer()->getParameter('kernel.project_dir');

        $this->mailer->expects($this->never())->method('addFile');
        $this->createTransaction();
        $actesNotification = $this->createActesNotification($this->mailerFactory);
        $actesNotification->sendAutomaticNotification();

        $this->assertTrue(
            $this->testHandler->hasRecordThatContains(
                "Erreur lors de la décompression",
                Level::Warning
            )
        );
    }

    private function deleteZip()
    {
        $fileSystem = self::getContainer()->get(Filesystem::class);
        $tmpdir = sys_get_temp_dir();

        $fileSystem->remove($tmpdir . '/abc-TACT--000000000--20170803-16.tar.gz');
    }
}
