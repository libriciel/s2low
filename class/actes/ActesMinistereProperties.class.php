<?php

class ActesMinistereProperties {


    const AUTHENTICATION_NONE = "NONE";
    const AUTHENTICATION_BASIC = "BASIC";
    const AUTHENTICATION_POST = "POST";

    public $url;

    public $authentification_type;

    public $login;
    public $password;

    public $client_certificate;
    public $client_certificate_key;
    public $client_certificate_key_password;



}