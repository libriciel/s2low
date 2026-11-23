<?php

declare(strict_types=1);

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
    case A_DETRUIRE = 21;
    case DETRUITE = 22;
    case ERREUR_SAE_DOCUMENTS_INDISPONIBLES = 23;
    case TRANSMIS_SANS_ACK = 24;

    public function tone(): StatusTone
    {
        return match ($this) {
            self::ACQUITTE,
            self::VALIDE,
            self::INFORMATION_DISPONIBLE,
            self::ACCEPTE_PAR_SAE,
            self::TRANSMIS_SANS_ACK => StatusTone::Done,
            self::POSTE,
            self::EN_ATTENTE,
            self::TRANSMIS,
            self::EN_TRAITEMENT,
            self::ENVOYE_AU_SAE,
            self::EN_ATTENTE_TRANSMISSION_SAE => StatusTone::InProgress,
            self::ATTENTE_SIGNEE,
            self::ATTENTE_POSTEE => StatusTone::Waiting,
            self::ERREUR,
            self::REFUSE,
            self::REFUSE_PAR_SAE,
            self::ERREUR_LORS_DE_L_ENVOI_SAE,
            self::ERREUR_SAE_DOCUMENTS_INDISPONIBLES => StatusTone::Failed,
            self::ANNULE,
            self::A_DETRUIRE,
            self::DETRUITE => StatusTone::Inactive,
        };
    }

    public function badgeLabel(): string
    {
        return match ($this) {
            self::ERREUR => 'Erreur',
            self::ANNULE => 'Annulé',
            self::POSTE => 'Posté',
            self::EN_ATTENTE => 'Attente transmission',
            self::TRANSMIS => 'Transmis',
            self::ACQUITTE => 'Acquittement reçu',
            self::VALIDE => 'Validé',
            self::REFUSE => 'Refusé',
            self::EN_TRAITEMENT => 'En traitement',
            self::INFORMATION_DISPONIBLE => 'Information disponible',
            self::ENVOYE_AU_SAE => 'Envoyé au SAE',
            self::ACCEPTE_PAR_SAE => 'Archivé par le SAE',
            self::REFUSE_PAR_SAE => 'Refusé par le SAE',
            self::ATTENTE_SIGNEE => "En attente d'être signé",
            self::ATTENTE_POSTEE => "En attente d'être postée",
            self::EN_ATTENTE_TRANSMISSION_SAE => 'Attente envoi SAE',
            self::ERREUR_LORS_DE_L_ENVOI_SAE => 'Erreur envoi SAE',
            self::A_DETRUIRE => 'A détruire',
            self::DETRUITE => 'Détruite',
            self::ERREUR_SAE_DOCUMENTS_INDISPONIBLES => 'Envoi SAE impossible',
            self::TRANSMIS_SANS_ACK => 'Transmis sans acquit',
        };
    }

    public function allowsSaeArchiving(): bool
    {
        return in_array($this, [
            self::ACQUITTE,
            self::INFORMATION_DISPONIBLE,
            self::REFUSE_PAR_SAE,
            self::ERREUR_LORS_DE_L_ENVOI_SAE,
        ], true);
    }
}
