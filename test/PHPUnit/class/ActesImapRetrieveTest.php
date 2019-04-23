<?php

class ActesImapRetrieveTest extends PHPUnit_Framework_TestCase {

	/**
	 * @throws Exception
	 */
    public function testRetrieve() {
		$logger = $this->getLogger();
        $actesImapRetrieve = new ActesImapRetrieve(
            $this->getImapProperties(),
            $this->getVFS(),
            $this->getImapMailBoxFactory(),
            $logger
        );
        $actesImapRetrieve->retrieve();

        $logs = $logger->getAllLog();
        $this->assertRegExp("#Connection au serveur IMAP#", $logs[1]);
        $this->assertRegExp("#Il y a 1 messages dans la boite au lettres#", $logs[2]);
        $this->assertRegExp("#Récupération du message : 13#", $logs[3]);

        $this->assertRegExp("#Sauvegarde du contenu du message HTML #", $logs[4]);
        $this->assertRegExp("#Sauvegarde de.*foo-école.pdf#", $logs[5]);
		$this->assertRegExp("#Déplacement du répertoire#", $logs[6]);
        $this->assertRegExp("#Suppression du message : 13#", $logs[7]);
    }

	/**
	 * @throws Exception
	 */
    public function testRetrieveDirectoryCreationFailed() {
		$logger = $this->getLogger();

        $actesImapRetrieve = new ActesImapRetrieve(
            $this->getImapProperties(),
            $this->getVFS()."/foo/bar",
            $this->getImapMailBoxFactory(),
			$logger
        );
        $this->setExpectedException(UnrecoverableException::class,"n'existe pas");
        $actesImapRetrieve->retrieve();
    }



    public function getVFS(){
    	$tmp = sys_get_temp_dir()."/test_actes_imap".mt_rand(0,mt_getrandmax());
    	mkdir ($tmp);
    	return $tmp;
    }

    private function getLogger(){
        $logger = new Logger();
        $logger->setLogType(Logger::TYPE_MEMORY);
        return $logger;
    }

    private function getImapProperties(){
        return new ActesImapProperties();
    }

    private function getImapMailBoxFactory(){


		$attachments= new StdClass;
		$attachments->name = "foo-école.pdf";
		$attachments->filePath = __FILE__;

        $incomingMail = $this->getMockBuilder('PhpImap\IncomingMail')->disableOriginalConstructor()->getMock();
		$incomingMail->textHtml = "mon texte html";
		$incomingMail->expects($this->any())->method('getAttachments')->willReturn([$attachments]);


        $mailBox = $this->getMockBuilder('PhpImap\Mailbox')->disableOriginalConstructor()->getMock();
		$mailBox->expects($this->any())->method('searchMailbox')->willReturn([13]);
		$mailBox->expects($this->any())->method('getMail')->willReturn($incomingMail);


		$imapMailBoxFactory = $this->getMockBuilder(ImapMailBoxFactory::class)->getMock();
		$imapMailBoxFactory->expects($this->any())->method('getInstance')->willReturn($mailBox);
        /** @var ImapMailBoxFactory $imapMailBoxFactory */
        return $imapMailBoxFactory;
    }



}