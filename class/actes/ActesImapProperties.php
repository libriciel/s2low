<?php

namespace S2lowLegacy\Class\actes;

class ActesImapProperties
{
    public string $host;
    public string $port;
    public string $login;
    public string $password;
    public string $imap_options;

    public function __construct($host, $port, $login, $password, $imap_options)
    {
        $this->host = $host;
        $this->port = $port;
        $this->login = $login;
        $this->password = $password;
        $this->imap_options = $imap_options;
    }
}
