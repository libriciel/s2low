<?php

namespace S2lowLegacy\Class\actes;

/**
 * Types de messages métiers
 * Etabli selon le cahier des charges ACTES mis à jour le 14/11/2011
 */
enum TypeMessageMetier: string
{
    //-----------------------------------------------------------------------------------------------------------
    // Transmission                                         1           emetteur                s2lowStoresTypeReponse  s2lowStoresAsEnveloppe
    // Transmission acte                                    1.1         Collectivité                false
    case Acte = 'Acte';
    // Accusé de réception                                  1.2         Préfecture                  false
    case ARActe = 'ARActe';
    // Anomalie dans le formulaire signalétique de l'acte   1.3         Préfecture                  false
    case AnomalieActe = 'AnomalieActe';


    //-----------------------------------------------------------------------------------------------------------
    // Courrier simple                                      2
    // Envoi de courrier simple                             2.1         Préfecture                  false
    case CourrierSimple = 'CourrierSimple';
    // Réponse à un courrier simple                         2.2         Collectivité                false
    case ReponseCourrierSimple = 'ReponseCourrierSimple';

    //-----------------------------------------------------------------------------------------------------------
    // Demande de pièces complémentaires                    3

    // Demande pièces complémentaires                       3.1         Préfecture                false(?)
    case DemandePieceComplementaire = 'DemandePieceComplementaire';
    // AR demande pièces complémentaires                    3.2         Collectivité                false
    case ARDemandePieceComplementaire = 'ARDemandePieceComplementaire';
    // Refus explicite pièces complémentaires               3.3         Collectivité                true
    case RefusPieceComplementaire = 'RefusPieceComplementaire';
    // Pièces complémentaires                               3.4         Collectivité                true
    case PieceComplementaire = 'PieceComplementaire';

    // AR pièce complémentaire ou refus                     3.5         Préfecture                  false
    case ARPieceComplementaire = 'ARPieceComplementaire';

    //-----------------------------------------------------------------------------------------------------------
    // Lettre d'observations                                4
    // Lettre d'observations                                4.1         Préfecture                  false
    case LettreObservations = 'LettreObservations';
    // AR Lettre d'observations                             4.2         Collectivité                  ?
    case ARLettreObservations = 'ARLettreObservations';
    // Refus Explicite réponse LO                           4.3         Collectivité                true
    case RejetLettreObservations = 'RejetLettreObservations';
    // Réponse LO                                           4.4         Collectivité                true
    case ReponseLettreObservations = 'ReponseLettreObservations';
    // AR réponse lettre d'observation ou refus             4.5         Préfecture                    ?
    case ARReponseRejetLettreObservations = 'ARReponseRejetLettreObservations';

    //-----------------------------------------------------------------------------------------------------------
    // Avis de déféré au TA                                 5.1         Préfecture                    ?
    case DefereTA = 'DefereTA';

    //-----------------------------------------------------------------------------------------------------------
    // Annulation de la transmission d'un acte
    // Annulation de transmission                           6.1         Collectivité
    case Annulation = 'Annulation';
    // Accusé de réception                                  6.2         Préfecture
    case ARAnnulation = 'ARAnnulation';

    //-----------------------------------------------------------------------------------------------------------
    // Transaction 7 : Demande de la structuration des matières et sous matières
    // Demande classif en matières                          7.1         Collectivité
    case DemandeClassification = 'DemandeClassification';
    // Classif en matières                                  7.2         Préfecture
    case ReponseClassificationSansChangement = 'ReponseClassificationSansChangement';
    // Classif en matières à jour                           7.3         Préfecture
    case RetourClassification = 'RetourClassification';

    public function typeTransaction(): TypeTransaction
    {

        return match ($this) {
            TypeMessageMetier::Acte,TypeMessageMetier::ARActe,TypeMessageMetier::AnomalieActe=>TypeTransaction::TransmissionActe,
            TypeMessageMetier::CourrierSimple,TypeMessageMetier::ReponseCourrierSimple=>TypeTransaction::CourrierSimple,
            TypeMessageMetier::DemandePieceComplementaire,TypeMessageMetier::ARDemandePieceComplementaire, TypeMessageMetier::RefusPieceComplementaire,TypeMessageMetier::PieceComplementaire,TypeMessageMetier::ARPieceComplementaire=>TypeTransaction::DemandePieceComplementaire,
            TypeMessageMetier::LettreObservations,TypeMessageMetier::ARLettreObservations,TypeMessageMetier::RejetLettreObservations,TypeMessageMetier::ReponseLettreObservations=>TypeTransaction::LettreDObservation,
            TypeMessageMetier::ARReponseRejetLettreObservations=>TypeTransaction::LettreDObservation,
            TypeMessageMetier::DefereTA=>TypeTransaction::DefereAuTribunalAdministratif ,
            TypeMessageMetier::Annulation,TypeMessageMetier::ARAnnulation => TypeTransaction::Annulation,
            TypeMessageMetier::DemandeClassification, TypeMessageMetier::ReponseClassificationSansChangement,TypeMessageMetier::RetourClassification =>TypeTransaction::DemandeDeClassification,
        };
    }

    public function getSousType(): int
    {
        return match ($this) {
            TypeMessageMetier::Acte => 1,
            TypeMessageMetier::ARActe => 2,
            TypeMessageMetier::AnomalieActe => 3,
            TypeMessageMetier::CourrierSimple => 1,
            TypeMessageMetier::ReponseCourrierSimple => 2,
            TypeMessageMetier::DemandePieceComplementaire => 1,
            TypeMessageMetier::ARDemandePieceComplementaire => 2,
            TypeMessageMetier::RefusPieceComplementaire => 3,
            TypeMessageMetier::PieceComplementaire => 4,
            TypeMessageMetier::ARPieceComplementaire => 5,
            TypeMessageMetier::LettreObservations => 1,
            TypeMessageMetier::ARLettreObservations => 2,
            TypeMessageMetier::RejetLettreObservations => 3,
            TypeMessageMetier::ReponseLettreObservations => 4,
            TypeMessageMetier::ARReponseRejetLettreObservations => 5,
            TypeMessageMetier::DefereTA => 1,
            TypeMessageMetier::Annulation => 1,
            TypeMessageMetier::ARAnnulation => 2,
            TypeMessageMetier::DemandeClassification => 1,
            TypeMessageMetier::RetourClassification => 2,
            TypeMessageMetier::ReponseClassificationSansChangement => 3,
        };
    }

    public function getCodeMessage(): string
    {
        return $this::typeTransaction()->value . '-' . $this->getSousType();
    }

    public function isSameAsStoredInS2low(
        TypeTransaction $typeS2low,
        ?int $sousTypeInS2low,
        bool $isSentFromS2low
    ): bool {

        if ($this->isSentFromS2low() !== $isSentFromS2low) {
            return false;
        }

        if ($this->typeTransaction() !== $typeS2low) {
            return false;
        }

        if (is_null($sousTypeInS2low) && $this->hasSousTypeStoredInS2low()) {
            return false;
        }

        if (is_null($sousTypeInS2low) && !$this->hasSousTypeStoredInS2low()) {
            return true;
        }

        return $this->getSousType() === $sousTypeInS2low;
    }

    private function hasSousTypeStoredInS2low()
    {
        return match ($this) {
            TypeMessageMetier::ARDemandePieceComplementaire,
            TypeMessageMetier::RefusPieceComplementaire,
            TypeMessageMetier::ARLettreObservations,
            TypeMessageMetier::RejetLettreObservations => true,
            default=> false
        };
    }

    private function isSentFromS2low(): bool
    {
        return match ($this) {
            TypeMessageMetier::Acte => true,
            TypeMessageMetier::ARActe => false,
            TypeMessageMetier::AnomalieActe => false,
            TypeMessageMetier::CourrierSimple => false,
            TypeMessageMetier::ReponseCourrierSimple => true,
            TypeMessageMetier::DemandePieceComplementaire => false,
            TypeMessageMetier::ARDemandePieceComplementaire => true,
            TypeMessageMetier::RefusPieceComplementaire => true,
            TypeMessageMetier::PieceComplementaire => true,
            TypeMessageMetier::ARPieceComplementaire => false,
            TypeMessageMetier::LettreObservations => false,
            TypeMessageMetier::ARLettreObservations => true,
            TypeMessageMetier::RejetLettreObservations => true,
            TypeMessageMetier::ReponseLettreObservations => true,
            TypeMessageMetier::ARReponseRejetLettreObservations => false,
            TypeMessageMetier::DefereTA => false,
            TypeMessageMetier::Annulation => true,
            TypeMessageMetier::ARAnnulation => false,
            TypeMessageMetier::DemandeClassification => true,
            TypeMessageMetier::RetourClassification => false,
            TypeMessageMetier::ReponseClassificationSansChangement => false,
        };
    }
}
