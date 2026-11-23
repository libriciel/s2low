<?php

declare(strict_types=1);

namespace S2low\Enum;

enum ActesStatus: int
{
    case ERREUR = -1;
    case ANNULE = 0;
    case POSTE = 1;
    case EN_ATTENTE_DE_TRANSMISSION = 2;
    case TRANSMIS = 3;
    case ACQUITTEMENT_RECU = 4;
    case VALIDE = 5;
    case REFUSE = 6;
    case DOCUMENT_RECU = 7;
    case ACQUITTEMENT_ENVOYE = 8;
    case DOCUMENT_ENVOYE = 9;
    case REFUS_D_ENVOI = 10;
    case ACQUITTEMENT_DE_DOCUMENT_RECU = 11;
    case ENVOYE_AU_SAE = 12;
    case ARCHIVE_PAR_LE_SAE = 13;
    case ERREUR_LORS_DE_L_ARCHIVAGE = 14;
    case RECU_PAR_LE_SAE = 15;
    case DETRUITE = 16;
    case EN_ATTENTE_D_ETRE_POSTEE = 17;
    case EN_ATTENTE_D_ETRE_SIGNEE = 18;
    case EN_ATTENTE_DE_TRANSMISSION_AU_SAE = 19;
    case ERREUR_LORS_DE_L_ENVOI_AU_SAE = 20;
    case DOCUMENT_RECU_PAS_D_AR = 21;
    case ERREUR_SAE_DOCUMENTS_INDISPONIBLES = 22;

    public function tone(): StatusTone
    {
        return match ($this) {
            self::ACQUITTEMENT_RECU,
            self::VALIDE,
            self::ACQUITTEMENT_ENVOYE,
            self::DOCUMENT_ENVOYE,
            self::ACQUITTEMENT_DE_DOCUMENT_RECU,
            self::ARCHIVE_PAR_LE_SAE,
            self::RECU_PAR_LE_SAE => StatusTone::Done,
            self::POSTE,
            self::EN_ATTENTE_DE_TRANSMISSION,
            self::TRANSMIS,
            self::ENVOYE_AU_SAE,
            self::EN_ATTENTE_DE_TRANSMISSION_AU_SAE => StatusTone::InProgress,
            self::DOCUMENT_RECU,
            self::EN_ATTENTE_D_ETRE_POSTEE,
            self::EN_ATTENTE_D_ETRE_SIGNEE,
            self::DOCUMENT_RECU_PAS_D_AR => StatusTone::Waiting,
            self::ERREUR,
            self::REFUSE,
            self::REFUS_D_ENVOI,
            self::ERREUR_LORS_DE_L_ARCHIVAGE,
            self::ERREUR_LORS_DE_L_ENVOI_AU_SAE,
            self::ERREUR_SAE_DOCUMENTS_INDISPONIBLES => StatusTone::Failed,
            self::ANNULE,
            self::DETRUITE => StatusTone::Inactive,
        };
    }

    public function badgeLabel(): string
    {
        return match ($this) {
            self::ERREUR => 'Erreur',
            self::ANNULE => 'Annulé',
            self::POSTE => 'Posté',
            self::EN_ATTENTE_DE_TRANSMISSION => 'Attente transmission',
            self::TRANSMIS => 'Transmis',
            self::ACQUITTEMENT_RECU => 'Acquittement reçu',
            self::VALIDE => 'Validé',
            self::REFUSE => 'Refusé',
            self::DOCUMENT_RECU => 'Document reçu',
            self::ACQUITTEMENT_ENVOYE => 'Acquittement envoyé',
            self::DOCUMENT_ENVOYE => 'Document envoyé',
            self::REFUS_D_ENVOI => "Refus d'envoi",
            self::ACQUITTEMENT_DE_DOCUMENT_RECU => 'Acq. document reçu',
            self::ENVOYE_AU_SAE => 'Envoyé au SAE',
            self::ARCHIVE_PAR_LE_SAE => 'Archivé par le SAE',
            self::ERREUR_LORS_DE_L_ARCHIVAGE => 'Erreur archivage',
            self::RECU_PAR_LE_SAE => 'Reçu par le SAE',
            self::DETRUITE => 'Détruite',
            self::EN_ATTENTE_D_ETRE_POSTEE => "En attente d'être postée",
            self::EN_ATTENTE_D_ETRE_SIGNEE => "En attente d'être signée",
            self::EN_ATTENTE_DE_TRANSMISSION_AU_SAE => 'Attente envoi SAE',
            self::ERREUR_LORS_DE_L_ENVOI_AU_SAE => 'Erreur envoi SAE',
            self::DOCUMENT_RECU_PAS_D_AR => 'Document reçu sans AR',
            self::ERREUR_SAE_DOCUMENTS_INDISPONIBLES => 'Envoi SAE impossible',
        };
    }

    public function allowsBulkClosing(): bool
    {
        return in_array($this, [
            self::ACQUITTEMENT_RECU,
            self::VALIDE,
            self::ERREUR_LORS_DE_L_ARCHIVAGE,
            self::EN_ATTENTE_D_ETRE_SIGNEE,
            self::ERREUR_LORS_DE_L_ENVOI_AU_SAE,
        ], true);
    }
}
