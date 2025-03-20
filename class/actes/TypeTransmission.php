<?php

namespace S2lowLegacy\Class\actes;

enum TypeTransmission: int
{
    case TransmissionActe = 1;
    case CourrierSimple = 2;
    case DemandePieceComplementaire = 3;
    case LettreDObservation = 4;
    case DefereAuTribunalAdministratif = 5;
    case Annulation = 6;
    case DemandeDeClassification = 7;


    /*
     *     protected $transactionTypes = array (
    "1" => "Transmission d'actes",
    "2" => "Courrier simple",
    "3" => "Demande de pièces complémentaires",
    "4" => "Lettre d'observation",
    "5" => "Déféré au Tribunal Administratif",
    "6" => "Annulation",
    "7" => "Demande de classification"
    );
     */
}
