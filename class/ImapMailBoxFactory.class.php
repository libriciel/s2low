<?php

class ImapMailBoxFactory {

	/**
	 * @param ActesImapProperties $actesImapProperties
	 * @param $tmp_folder
	 * @return \PhpImap\Mailbox
	 * @throws \PhpImap\Exception
	 */
    public function getInstance(ActesImapProperties $actesImapProperties,$tmp_folder){

		return new PhpImap\Mailbox(
			"{{$actesImapProperties->host}:{$actesImapProperties->port}/imap/novalidate-cert}INBOX",
			$actesImapProperties->login,
			$actesImapProperties->password,
			$tmp_folder
		);

    }

}