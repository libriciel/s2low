<?php

namespace S2low\Enum;

enum HeliosStatus: int
{
    case ERREUR = -1;
    case ANNULE = 0;
    case POSTE = 1;
    case EN_ATTENTE = 2;
    case TRANSMIS = 3;
    case ACQUITTE = 4;
    case VALIDE = 5;
    case REFUSE = 6;
    case EN_TRAITEMENT = 7;
    case INFORMATION_DISPONIBLE = 8;
    case ENVOYE_AU_SAE = 9;
    case ACCEPTE_PAR_SAE = 10;
    case REFUSE_PAR_SAE = 11;
    case ATTENTE_SIGNEE = 13;
    case ATTENTE_POSTEE = 14;
    case EN_ATTENTE_TRANSMISSION_SAE = 19;
    case ERREUR_LORS_DE_L_ENVOI_SAE = 20;
    case DETRUITE = 22;
    case TRANSMIS_SANS_ACK = 24;
}
