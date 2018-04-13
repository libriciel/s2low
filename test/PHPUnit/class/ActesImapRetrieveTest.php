<?php

class ActesImapRetrieveTest extends PHPUnit_Framework_TestCase {

    public function testRetrieve() {
		$logger = $this->getLogger();
        $actesImapRetrieve = new ActesImapRetrieve(
            $this->getImapProperties(),
            $this->getVFS(),
            $this->getImapFetchServerFactory(),
            $logger
        );
        $actesImapRetrieve->retrieve();

        $logs = $logger->getAllLog();
        $this->assertRegExp("#Connection au serveur IMAP#", $logs[1]);
        $this->assertRegExp("#Il y a 1 messages dans la boite au lettres#", $logs[2]);
        $this->assertRegExp("#Récupération du message : 13#", $logs[3]);

        $this->assertRegExp("#Sauvegarde du contenu du message HTML #", $logs[4]);
        $this->assertRegExp("#Sauvegarde de.*foo.pdf#", $logs[5]);
		$this->assertRegExp("#Déplacement du répertoire#", $logs[6]);
        $this->assertRegExp("#Suppression du message : 13#", $logs[7]);
    }


    public function testRetrieveDirectoryCreationFailed() {
		$logger = $this->getLogger();
        $actesImapRetrieve = new ActesImapRetrieve(
            $this->getImapProperties(),
            $this->getVFS()."/foo/bar",
            $this->getImapFetchServerFactory(),
			$logger
        );
        $this->setExpectedException("Exception","Impossible de déplacer");
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

    private function getImapFetchServerFactory(){

        $overview = new stdClass();
        $overview->message_id = "13";
        $attachement = $this->getMockBuilder('\Fetch\Attachment')->disableOriginalConstructor()->getMock();
        $attachement->expects($this->any())->method('getFileName')->willReturn("foo.pdf");


        $message = $this->getMockBuilder('\Fetch\Message')->disableOriginalConstructor()->getMock();
        $message->expects($this->any())->method('getOverview')->willReturn($overview);
        $message->expects($this->any())->method('getAttachments')->willReturn(array($attachement));

        $fetchServer = $this->getMockBuilder('\Fetch\Server')->disableOriginalConstructor()->getMock();
        $fetchServer->expects($this->any())->method('getMessages')->willReturn(array($message));

        $imapFetchServerFactory = $this->getMockBuilder('ImapFetchServerFactory')->getMock();
        $imapFetchServerFactory->expects($this->any())->method('getInstance')->willReturn($fetchServer);
        /** @var ImapFetchServerFactory $imapFetchServerFactory */
        return $imapFetchServerFactory;
    }
}