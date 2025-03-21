<?php

namespace S2lowLegacy\Class\actes;

/**
 * Les différents types de transaction tels que définis par le cahier des charges ACTES
 */
enum TypeTransaction: int
{
    case TransmissionActe = 1;
    case CourrierSimple = 2;
    case DemandePieceComplementaire = 3;
    case LettreDObservation = 4;
    case DefereAuTribunalAdministratif = 5;
    case Annulation = 6;
    case DemandeDeClassification = 7;
}
