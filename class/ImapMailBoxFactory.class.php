<?php

use PhpImap\Mailbox;

class ImapMailBoxFactory
{
    /**
     * @param ActesImapProperties $actesImapProperties
     * @param $tmp_folder
     * @return \PhpImap\Mailbox
     * @throws \PhpImap\Exceptions\InvalidParameterException
     */
    public function getInstance(ActesImapProperties $actesImapProperties, $tmp_folder): Mailbox
    {

        // TODO : "{{$actesImapProperties->host}:993/imap/notls/novalidate-cert}INBOX" ne fonctionnerait pas...
        // Il faut adapter par ex en {{$actesImapProperties->host}:993/imap/ssl/novalidate-cert}INBOX
        // Issue ?

        return new PhpImap\Mailbox(
            "{{$actesImapProperties->host}:{$actesImapProperties->port}/imap/notls/novalidate-cert}INBOX",
            $actesImapProperties->login,
            $actesImapProperties->password,
            $tmp_folder
        );
    }
}
