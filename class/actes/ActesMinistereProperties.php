<?php

namespace S2lowLegacy\Class\actes;

class ActesMinistereProperties
{
    public const AUTHENTICATION_NONE = "NONE";
    public const AUTHENTICATION_BASIC = "BASIC";
    public const AUTHENTICATION_POST = "POST";

    public function __construct(
        public string $url,
        public string $authentification_type,
        public string $login,
        public string $password,
        public string $client_certificate,
        public string $client_certificate_key,
        public string $client_certificate_key_password,
        public string $adapt_protocol,
        public string $server_certificate_path,
        public bool $use_legacy_protocol
    ) {
    }
}
