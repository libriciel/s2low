<?php

use S2low\Services\MailActesNotifications\MailerSymfony;
use S2low\Services\MailActesNotifications\MailerSymfonyFactory;
use S2lowLegacy\Class\actes\ActesNotification;
use S2lowLegacy\Class\actes\ActesPdf;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\actes\IActesPdf;
use S2lowLegacy\Class\IActesWorkspace;
use S2lowLegacy\Class\Mailer;
use S2lowLegacy\Class\TmpFolder;
use S2lowLegacy\Lib\ObjectInstancier;
use PHPUnit\Framework\MockObject\MockObject;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class ActesNotificationsTest extends \S2low\Tests\S2lowSymfonyWebTestCase
{
    /**
     * @var MockObject|Mailer
     */
    private $mailer;
    /** @var int  */
    private $transaction_id;
    /**
     * @var ActesNotification|object|ObjectInstancier
     */
    private $actesNotification;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->getObjectInstancier()->set(IActesPdf::class, new ActesPdf(SITEROOT . "public.ssl/custom/images/bandeau-s2low-190.jpg"));
        $this->mailer = $this->getMockBuilder(MailerSymfony::class)
            ->disableOriginalConstructor()->getMock();
        $mailerFactory = $this->getMockBuilder(MailerSymfonyFactory::class)
            ->disableOriginalConstructor()->getMock();
        $mailerFactory->method("getInstance")->willReturn($this->mailer);
        $this->getObjectInstancier()->set(MailerSymfonyFactory::class, $mailerFactory);

        $this->transaction_id = $this->createTransaction(4);

        $loader = new FilesystemLoader(__DIR__ . "/../../../templates");
        $twig = new Environment($loader);

        $this->getObjectInstancier()->set("pdf_stamp_url", "");
        $this->getObjectInstancier()->set(Environment::class, $twig);


        $this->actesNotification = $this->getObjectInstancier()->get(ActesNotification::class);
    }

    /**
     * @throws Exception
     */
    public function testNotify()
    {
        copy(
            __DIR__ . '/../../../test/PHPUnit/class/actes/fixtures/abc-TACT--000000000--20170803-16.tar.gz',
            $this->getActesWorkspace()->getFilesUploadRoot() . "/abc-TACT--000000000--20170803-16.tar.gz"
        );

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

        $actesTransactionsSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);
        $actesTransactionsSQL->updateStatus($transaction_id, 4, "test");

        return $transaction_id;
    }

    /**
     * @throws Exception
     */
    public function testNotifyWithWrongZipWillSendMailAnyway()
    {
        copy(
            __DIR__ . "/../../../test/PHPUnit/class/actes/fixtures/convention-exemple.pdf",
            $this->getActesWorkspace()->getFilesUploadRoot() . "/abc-TACT--000000000--20170803-16.tar.gz"
        );
        $this->mailer->expects($this->exactly(3))->method('sendMailWithHtml');

        $this->actesNotification->sendAutomaticNotification();
    }

    /**
     * @throws Exception
     */
    public function testNotifyWithWrongZipWillnotAddFiles()
    {
        copy(
            __DIR__ . "/../../../test/PHPUnit/class/actes/fixtures/convention-exemple.pdf",
            $this->getActesWorkspace()->getFilesUploadRoot() . "/abc-TACT--000000000--20170803-16.tar.gz"
        );
        $this->mailer->expects($this->never())->method('addFile');

        $this->actesNotification->sendAutomaticNotification();
    }

    /**
     * @throws Exception
     */
    public function testNotifyWithWrongZipWillLogErrors()
    {
        copy(
            __DIR__ . "/../../../test/PHPUnit/class/actes/fixtures/convention-exemple.pdf",
            $this->getActesWorkspace()->getFilesUploadRoot() . "/abc-TACT--000000000--20170803-16.tar.gz"
        );
        $this->mailer->expects($this->never())->method('addFile');

        $this->actesNotification->sendAutomaticNotification();

        $logRecords = $this->getLogRecords();
        $this->assertMatchesRegularExpression(
            "#Erreur lors de la décompression#",
            $logRecords[2]["message"]
        );
    }
}
