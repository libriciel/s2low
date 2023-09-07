<?php

namespace S2lowLegacy\Class;

use PhpImap\Mailbox;
use S2lowLegacy\Class\actes\ActesImapProperties;

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
        return new Mailbox(
            "{{$actesImapProperties->host}:{$actesImapProperties->port}{$actesImapProperties->imap_options}}INBOX",
            $actesImapProperties->login,
            $actesImapProperties->password,
            $tmp_folder
        );
    }
}
