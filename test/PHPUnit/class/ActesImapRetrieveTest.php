<?php

use S2lowLegacy\Class\actes\ActesImapProperties;
use S2lowLegacy\Class\actes\ActesImapRetrieve;
use S2lowLegacy\Class\ImapMailBoxFactory;
use S2lowLegacy\Class\S2lowLogger;
use S2lowLegacy\Class\WorkerScript;
use S2lowLegacy\Lib\SigTermHandler;

class ActesImapRetrieveTest extends S2lowSimpleTestCase
{
    /**
     * @throws Exception
     */
    public function testRetrieve()
    {
        $s2lowLogger = $this->getObjectInstancier()->get(S2lowLogger::class);

        $actesImapRetrieve = new ActesImapRetrieve(
            $this->getImapProperties(),
            $this->getVFS(),
            $this->getImapMailBoxFactory(),
            $s2lowLogger,
            SigTermHandler::getInstance(),
            $this->getWorkerScript()
        );
        $actesImapRetrieve->retrieve();

        $logs = $this->getLogRecords();

        $this->assertMatchesRegularExpression(
            "#Connexion au serveur IMAP mail.example.com:993/imap/ssl avec l'utilisateur login#",
            $logs[1][S2lowLogger::MESSAGE]
        );
        $this->assertMatchesRegularExpression(
            "#Il y a 1 messages dans la boite au lettres#",
            $logs[2][S2lowLogger::MESSAGE]
        );
        $this->assertMatchesRegularExpression("#Récupération du message : 13#", $logs[3][S2lowLogger::MESSAGE]);

        $this->assertMatchesRegularExpression(
            "#Sauvegarde du contenu du message HTML #",
            $logs[4][S2lowLogger::MESSAGE]
        );
        $this->assertMatchesRegularExpression("#Sauvegarde de.*foo-école.pdf#", $logs[5][S2lowLogger::MESSAGE]);
        $this->assertMatchesRegularExpression("#Déplacement du répertoire#", $logs[7][S2lowLogger::MESSAGE]);
        $this->assertMatchesRegularExpression("#Suppression du message : 13#", $logs[8][S2lowLogger::MESSAGE]);
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
        $s2lowLogger = $this->getObjectInstancier()->get(S2lowLogger::class);

        $actesImapRetrieve = new ActesImapRetrieve(
            $this->getImapProperties(),
            $this->getVFS(),
            $this->getImapMailBoxFactory(""),
            $s2lowLogger,
            SigTermHandler::getInstance(),
            $this->getWorkerScript()
        );
        $actesImapRetrieve->retrieve();

        $logs = $this->getLogRecords();

        $this->assertMatchesRegularExpression(
            "#Connexion au serveur IMAP mail.example.com:993/imap/ssl avec l'utilisateur login#",
            $logs[1][S2lowLogger::MESSAGE]
        );
        $this->assertMatchesRegularExpression(
            "#Il y a 1 messages dans la boite au lettres#",
            $logs[2][S2lowLogger::MESSAGE]
        );
        $this->assertMatchesRegularExpression("#Récupération du message : 13#", $logs[3][S2lowLogger::MESSAGE]);

        $this->assertMatchesRegularExpression(
            "#Le corps du mail est vide, il ne sera pas sauvegardé#",
            $logs[4][S2lowLogger::MESSAGE]
        );
        $this->assertMatchesRegularExpression("#Sauvegarde de.*foo-école.pdf#", $logs[5][S2lowLogger::MESSAGE]);
        $this->assertMatchesRegularExpression("#Déplacement du répertoire#", $logs[7][S2lowLogger::MESSAGE]);
        $this->assertMatchesRegularExpression("#Suppression du message : 13#", $logs[8][S2lowLogger::MESSAGE]);
    }


    public function getVFS()
    {
        $tmp = sys_get_temp_dir() . "/test_actes_imap" . mt_rand(0, mt_getrandmax());
        mkdir($tmp);
        return $tmp;
    }


    private function getImapProperties()
    {
        $actesImapProperties = new ActesImapProperties();
        $actesImapProperties->host = 'mail.example.com';
        $actesImapProperties->port = 993;
        $actesImapProperties->imap_options = '/imap/ssl';
        $actesImapProperties->login = 'login';
        $actesImapProperties->password = 'password';
        return $actesImapProperties;
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
}
