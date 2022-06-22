<?php

class PastellProperties
{
    const DESTINATION_SAE = 'SAE';
    const DESTINATION_GED = 'GED';
    const MAX_ID = 2147483647; /* (signed) integer max size in PostgreSQL*/

    public $url;
    public $login;
    public $password;
    public $id_e;

    public $actes_flux_id = 'actes-generique';
    public $actes_action = 'send-archive';
    public $actes_destination = self::DESTINATION_SAE;
    public $actes_send_auto = false;
    public $actes_transaction_id_min = 0;
    public $actes_transaction_id_max = self::MAX_ID;

    public $helios_flux_id = 'helios-generique';
    public $helios_action = 'send-archive';
    public $helios_destination = self::DESTINATION_SAE;
    public $helios_send_auto = false;
    public $helios_transaction_id_min = 0;
    public $helios_transaction_id_max = self::MAX_ID;
}
