<?php

class ActesImapRetrieveTest extends S2lowSimpleTestCase {

	/**
	 * @throws Exception
	 */
    public function testRetrieve() {

    	$s2lowLogger = $this->getObjectInstancier()->get(S2lowLogger::class);

        $actesImapRetrieve = new ActesImapRetrieve(
            $this->getImapProperties(),
            $this->getVFS(),
            $this->getImapMailBoxFactory(),
			$s2lowLogger,
			SigTermHandler::getInstance()
        );
        $actesImapRetrieve->retrieve();

        $logs = $this->getLogRecords();

        $this->assertRegExp("#Connection au serveur IMAP#", $logs[1][S2lowLogger::MESSAGE]);
        $this->assertRegExp("#Il y a 1 messages dans la boite au lettres#", $logs[2][S2lowLogger::MESSAGE]);
        $this->assertRegExp("#Récupération du message : 13#", $logs[3][S2lowLogger::MESSAGE]);

        $this->assertRegExp("#Sauvegarde du contenu du message HTML #", $logs[4][S2lowLogger::MESSAGE]);
        $this->assertRegExp("#Sauvegarde de.*foo-école.pdf#", $logs[5][S2lowLogger::MESSAGE]);
		$this->assertRegExp("#Déplacement du répertoire#", $logs[6][S2lowLogger::MESSAGE]);
        $this->assertRegExp("#Suppression du message : 13#", $logs[7][S2lowLogger::MESSAGE]);
    }

	/**
	 * @throws Exception
	 */
    public function testRetrieveDirectoryCreationFailed() {
		$s2lowLogger = $this->getObjectInstancier()->get(S2lowLogger::class);


		$actesImapRetrieve = new ActesImapRetrieve(
            $this->getImapProperties(),
            $this->getVFS()."/foo/bar",
            $this->getImapMailBoxFactory(),
			$s2lowLogger,
			SigTermHandler::getInstance()
        );
        $this->setExpectedException(UnrecoverableException::class,"n'existe pas");
        $actesImapRetrieve->retrieve();
    }



    public function getVFS(){
    	$tmp = sys_get_temp_dir()."/test_actes_imap".mt_rand(0,mt_getrandmax());
    	mkdir ($tmp);
    	return $tmp;
    }


    private function getImapProperties(){
        return new ActesImapProperties();
    }

    private function getImapMailBoxFactory(){


		$attachments= new StdClass;
		$attachments->name = "foo-école.pdf";
		$attachments->filePath = __FILE__;

        $incomingMail = $this->getMockBuilder('PhpImap\IncomingMail')->disableOriginalConstructor()->getMock();
		$incomingMail->{'textHtml'} = "mon texte html";
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