<?php

namespace S2low\Services\XMLFromDGFiP;

use Exception;
use S2low\DTO\PesEntrantHandlingResult;
use S2low\ProcessingResults\CreatePesRetour;
use S2low\Services\XMLFromDGFiP\ParsablesPes\PesDocumentType;
use S2low\Services\XMLFromDGFiP\ParsablesPes\Values\Siret;
use S2low\Services\XMLFromDGFiP\PesBuilder\ParsedPes;
use S2lowLegacy\Model\AuthoritySiretSQL;
use SplFileObject;

class PesRetourStrategy implements ParsedXmlFileStrategy
{
    public function __construct(
        private readonly AuthoritySiretSQL $authoritySiretSQL,
        private readonly CreatePesRetour $createPesRetour,
    ) {
    }

    public function canHandle(ParsedPes $pes): bool
    {
        return $pes->hasType(PesDocumentType::PES_RETOUR) && !$pes->hasXsdErrors();
    }

    public function handle(SplFileObject $fileObject, ParsedPes $pes): PesEntrantHandlingResult
    {
        $siret = $pes->getStringValue(Siret::KEY);

        $authority_list = $this->authoritySiretSQL->authorityList($siret);

        if (!$authority_list) {
            throw new Exception("La collectivité $siret n'est pas abonnée à l'application Comptabilité Publique du TdT, elle n'est donc pas autorisée à recevoir le PES_Retour ");
        }

        if (count($authority_list) > 1) {
            throw new Exception("Le SIRET $siret est associé à plusieurs collectivités. Le PES_Retour n'est donc pas attribué");
        }
        $authorityId = $authority_list[0]['authority_id'];

        $this->createPesRetour->execute(
            $siret,
            $authorityId,
            $fileObject,
        );

        return new PesEntrantHandlingResult();
    }
}
