<?php

namespace S2lowLegacy\Class\actes;

class ActesMinistereProperties
{
    public const AUTHENTICATION_NONE = "NONE";
    public const AUTHENTICATION_BASIC = "BASIC";
    public const AUTHENTICATION_POST = "POST";

    public $url;

    public $authentification_type;

    public $login;
    public $password;

    public $client_certificate;
    public $client_certificate_key;
    public $client_certificate_key_password;

    public $server_certificate_path;
}
