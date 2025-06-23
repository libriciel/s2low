<?php

use PHPUnit\ActesUtilitiesTestTrait;
use S2lowLegacy\Class\actes\ActesPdf;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\actes\BordereauPdfGenerator;
use S2lowLegacy\Class\actes\IActesPdf;

class ActesPdfTest extends S2lowTestCase
{
    use ActesUtilitiesTestTrait;

    public function testCreatePdf()
    {
        $this->getObjectInstancier()->set(IActesPdf::class, new ActesPdf(SITEROOT . "public.ssl/custom/images/bandeau-s2low-190.jpg"));

        $transaction_id = $this->createTransactionOfType(4);

        $bordereauPdfGenerator = $this->getObjectInstancier()->get(BordereauPdfGenerator::class);

        $this->assertNotEmpty($bordereauPdfGenerator->generate($transaction_id, "test_pdf.pdf", true, "S"));
    }

    protected function getActesTransactionsSQL(): ActesTransactionsSQL
    {
        return self::getContainer()->get(ActesTransactionsSQL::class);
    }
}
