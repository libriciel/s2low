<?php

namespace S2low\Services\XMLFromDGFiP;

use Exception;
use S2low\DTO\PesEntrantHandlingResult;
use S2low\ProcessingResults\ChangeTransactionStatus;
use S2low\Services\XMLFromDGFiP\ParsablesPes\PesDocumentType;
use S2low\Services\XMLFromDGFiP\ParsablesPes\Values\CodCol;
use S2low\Services\XMLFromDGFiP\ParsablesPes\Values\NomFich;
use S2low\Services\XMLFromDGFiP\PesBuilder\ParsedPes;
use S2lowLegacy\Model\HeliosTransactionsSQL;
use SplFileObject;

class XmlLinkableToTransactionStrategy implements ParsedXmlFileStrategy
{
    public function __construct(
        private readonly ChangeTransactionStatus $changeTransactionStatus,
        private readonly HeliosTransactionsSQL $heliosTransactionsSQL
    ) {
    }

    public function canHandle(ParsedPes $pes): bool
    {
        return (!$pes->hasType(PesDocumentType::PES_ACQUIT) || $pes->hasXsdErrors()) && $pes->hasValue(NomFich::KEY);
    }

    /**
     * @throws \S2low\Exceptions\MissingFieldInParsedPesException
     * @throws \S2low\Exceptions\PesEntrantSavingException
     */
    public function handle(SplFileObject $fileObject, ParsedPes $pes): PesEntrantHandlingResult
    {
        $nomFich = $pes->getStringValue(NomFich::KEY);
        $codCol = $pes->getStringValue(CodCol::KEY, true);

        $transactionId = $this->heliosTransactionsSQL->findTransactionId(
            $nomFich,
            $codCol,
        );

        if (is_null($transactionId)) {
            throw new Exception("Le couple NomFic $nomFich et CodCol $codCol n'est associé à aucune transaction dans la base de données");
        }


        $message = "Transaction $transactionId : erreur retournée par Helios";

        $this->changeTransactionStatus->execute(
            $transactionId,
            HeliosTransactionsSQL::ERREUR,
            $message,
            $fileObject
        );

        return new PesEntrantHandlingResult();
    }
}
