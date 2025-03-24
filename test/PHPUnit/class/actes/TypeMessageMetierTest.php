<?php

namespace PHPUnit\class\actes;

use PHPUnit\Framework\TestCase;
use S2lowLegacy\Class\actes\TypeMessageMetier;

class TypeMessageMetierTest extends TestCase
{
    /**
     * @dataProvider typesEtCodes
     */
    public function testCodesMetier(TypeMessageMetier $typeMessageMetier, string $expectedCode): void
    {
        self::assertSame(
            $expectedCode,
            $typeMessageMetier->getCodeMessage()
        );
    }

    public function typesEtCodes(): iterable
    {
        return
        [
            [TypeMessageMetier::Acte, '1-1'],
            [TypeMessageMetier::ARActe, '1-2'],
            [TypeMessageMetier::AnomalieActe, '1-3'],
            [TypeMessageMetier::CourrierSimple, '2-1'],
            [TypeMessageMetier::ReponseCourrierSimple, '2-2'],
            [TypeMessageMetier::DemandePieceComplementaire, '3-1'],
            [TypeMessageMetier::ARDemandePieceComplementaire, '3-2'],
            [TypeMessageMetier::RefusPieceComplementaire, '3-3'],
            [TypeMessageMetier::PieceComplementaire, '3-4'],
            [TypeMessageMetier::ARPieceComplementaire, '3-5'],
            [TypeMessageMetier::LettreObservations, '4-1'],
            [TypeMessageMetier::ARLettreObservations, '4-2'],
            [TypeMessageMetier::RejetLettreObservations, '4-3'],
            [TypeMessageMetier::ReponseLettreObservations, '4-4'],
            [TypeMessageMetier::ARReponseRejetLettreObservations, '4-5'],
            [TypeMessageMetier::DefereTA, '5-1'],
            [TypeMessageMetier::Annulation, '6-1'],
            [TypeMessageMetier::ARAnnulation, '6-2'],
            [TypeMessageMetier::DemandeClassification, '7-1'],
            [TypeMessageMetier::RetourClassification, '7-2'],
            [TypeMessageMetier::ReponseClassificationSansChangement, '7-3']
        ];
    }
}
