<?php

namespace S2low\Enum;

enum ActesStatus : int
{
    case ERREUR = -1;
    case ANNULE = 0;
    case POSTE = 1;
    case EN_ATTENTE_TRANSMISSION = 2;
    case TRANSMIS = 3;
    case ACQUITTEMENT_RECU = 4;
    case VALIDE = 5;
    case REFUSE = 6;
    case DOCUMENT_RECU = 7;
    case ACQUITTEMENT_ENVOYE = 8;
    case DOCUMENT_ENVOYE = 9;
    case REFUS_ENVOI = 10;
    case ACQUITTEMENT_DOC_RECU = 11;
    case ENVOYE_SAE = 12;
    case ARCHIVE_SAE = 13;
    case ERREUR_ARCHIVAGE = 14;
    case RECU_SAE = 15;
    case DETRUITE = 16;
    case EN_ATTENTE_POST = 17;
    case EN_ATTENTE_SIGNATURE = 18;
    case EN_ATTENTE_TRANSMISSION_SAE = 19;
    case ERREUR_ENVOI_SAE = 20;
    case DOCUMENT_RECU_SANS_AR = 21;
    case IMPOSSIBLE_ENVOYER_SAE = 22;
    case EN_COURS = 999;
}
