<?php

namespace PHPUnit\class;

use Exception;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger;
use PhpImap;
use S2lowTestCase;
use stdClass;
use S2lowLegacy\Class\actes\ActesImapProperties;
use S2lowLegacy\Class\actes\ActesImapRetrieve;
use S2lowLegacy\Class\ImapMailBoxFactory;
use S2lowLegacy\Class\S2lowLogger;
use S2lowLegacy\Class\WorkerScript;
use S2lowLegacy\Lib\SigTermHandler;

class ActesImapRetrieveTest extends S2lowTestCase
{
    /**
     * @throws Exception
     */
    public function testRetrieve()
    {
        $testHandler = new TestHandler();
        $logger = new Logger('test');
        $logger->pushHandler($testHandler);

        $s2lowLogger =  new S2lowLogger(
            $logger
        );

        $actesImapRetrieve = new ActesImapRetrieve(
            $this->getImapProperties(),
            $this->getVFS(),
            $this->getImapMailBoxFactory(),
            $s2lowLogger,
            SigTermHandler::getInstance(),
            $this->getWorkerScript()
        );
        $actesImapRetrieve->retrieve();

        $this->assertTrue(
            $testHandler->hasRecord(
                "Connexion au serveur IMAP mail.example.com:993/imap/ssl avec l'utilisateur login",
                Level::Info
            )
        );

        $this->assertTrue(
            $testHandler->hasRecord(
                "Il y a 1 messages dans la boite au lettres",
                Level::Info
            )
        );

        $this->assertTrue(
            $testHandler->hasRecord(
                "Récupération du message : 13",
                Level::Info
            )
        );

        $this->assertTrue(
            $testHandler->hasRecord(
                "Suppression du message : 13",
                Level::Info
            )
        );

        $this->assertTrue(
            $testHandler->hasInfoThatContains(
                "Sauvegarde du contenu du message HTML"
            )
        );

        $this->assertTrue(
            $testHandler->hasInfoThatContains(
                "Sauvegarde de"
            )
        );

        $this->assertTrue(
            $testHandler->hasInfoThatContains(
                "foo-école.pdf"
            )
        );

        $this->assertTrue(
            $testHandler->hasInfoThatContains(
                "Déplacement du répertoire"
            )
        );
    }

    private function getImapProperties(): ActesImapProperties
    {
        return new ActesImapProperties(
            'mail.example.com',
            993,
            'login',
            'password',
            '/imap/ssl'
        );
    }

    public function getVFS()
    {
        $tmp = sys_get_temp_dir() . "/test_actes_imap" . mt_rand(0, mt_getrandmax());
        mkdir($tmp);
        return $tmp;
    }

    private function getImapMailBoxFactory($mailHtmlText = "mon texte html")
    {
        $attachments = new StdClass();
        $attachments->name = "foo-école.pdf";
        $attachments->filePath = __FILE__;

        $incomingMail = $this->getMockBuilder(PhpImap\IncomingMail::class)->disableOriginalConstructor()->getMock();
        $incomingMail->{'textHtml'} = $mailHtmlText;
        $incomingMail->method('getAttachments')->willReturn([$attachments]);


        $mailBox = $this->getMockBuilder(PhpImap\Mailbox::class)->disableOriginalConstructor()->getMock();
        $mailBox->method('searchMailbox')->willReturn([13]);
        $mailBox->method('getMail')->willReturn($incomingMail);


        $imapMailBoxFactory = $this->getMockBuilder(ImapMailBoxFactory::class)->getMock();
        $imapMailBoxFactory->method('getInstance')->willReturn($mailBox);
        /** @var ImapMailBoxFactory $imapMailBoxFactory */
        return $imapMailBoxFactory;
    }

    /**
     * @return WorkerScript
     */
    private function getWorkerScript()
    {
        $workerScript = $this->getMockBuilder(WorkerScript::class)->disableOriginalConstructor()->getMock();
        $workerScript->method('putJobByClassName')->willReturn(true);
        /** @var WorkerScript $workerScript */
        return $workerScript;
    }

    /**
     * @throws Exception
     */
    public function testRetrieveDirectoryCreationFailed()
    {
        $s2lowLogger = $this->getObjectInstancier()->get(S2lowLogger::class);


        $actesImapRetrieve = new ActesImapRetrieve(
            $this->getImapProperties(),
            $this->getVFS() . "/foo/bar",
            $this->getImapMailBoxFactory(),
            $s2lowLogger,
            SigTermHandler::getInstance(),
            $this->getWorkerScript()
        );
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("n'existe pas");
        $actesImapRetrieve->retrieve();
    }

    /**
     * @throws Exception
     */
    public function testRetrieveMailWithEmptyBody()
    {
        $testHandler = new TestHandler();
        $logger = new Logger('test');
        $logger->pushHandler($testHandler);

        $s2lowLogger = new S2lowLogger(
            $logger
        );

        $actesImapRetrieve = new ActesImapRetrieve(
            $this->getImapProperties(),
            $this->getVFS(),
            $this->getImapMailBoxFactory(""),
            $s2lowLogger,
            SigTermHandler::getInstance(),
            $this->getWorkerScript()
        );
        $actesImapRetrieve->retrieve();

        $this->assertTrue(
            $testHandler->hasRecord(
                "Connexion au serveur IMAP mail.example.com:993/imap/ssl avec l'utilisateur login",
                Level::Info
            )
        );

        $this->assertTrue(
            $testHandler->hasRecord(
                "Il y a 1 messages dans la boite au lettres",
                Level::Info
            )
        );

        $this->assertTrue(
            $testHandler->hasRecord(
                "Récupération du message : 13",
                Level::Info
            )
        );

        $this->assertTrue(
            $testHandler->hasRecord(
                "Le corps du mail est vide, il ne sera pas sauvegardé",
                Level::Info
            )
        );

        $this->assertTrue(
            $testHandler->hasRecord(
                "Suppression du message : 13",
                Level::Info
            )
        );


        $this->assertTrue(
            $testHandler->hasInfoThatContains(
                "Sauvegarde de"
            )
        );

        $this->assertTrue(
            $testHandler->hasInfoThatContains(
                "foo-école.pdf"
            )
        );

        $this->assertTrue(
            $testHandler->hasInfoThatContains(
                "Déplacement du répertoire"
            )
        );
    }
}
