<?php

namespace S2lowLegacy\Class\actes;

class ActesImapProperties
{
    public function __construct(
        public string $host,
        public mixed $port,
        public string $login,
        public string $password,
        public string $imap_options,
    ) {
    }
}
