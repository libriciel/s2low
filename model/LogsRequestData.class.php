<?php

class LogsRequestData
{
    const STATE_ASKING = 1;
    const STATE_CANCEL = 2;
    const STATE_AVAILABLE = 3;
    const STATE_ARCHIVED = 4;


    public $user_id_demandeur;

    public $user_id;
    public $authority_id;
    public $authority_group_id;

    public $date_debut;
    public $date_fin;

    public $state;

    public $date_demande;
    public $date_traitement;


    public static function getStateString($state)
    {
        $state_libelle = array(1 => "demande","annulé","disponible","archivé");
        return $state_libelle[$state];
    }
}
