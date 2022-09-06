<?php

use S2lowLegacy\Class\actes\ActesPdfLegacy;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\BordereauPdfGenerator;
use S2lowLegacy\Class\actes\IActesPdf;
use S2lowLegacy\Class\TmpFolder;

class BordereauPdfGeneratorTest extends S2lowTestCase
{
    use ActesUtilitiesTestTrait;

    public function testGenerate()
    {
        $tmpFolder = new TmpFolder();
        $tmp_folder = $tmpFolder->create();

        $filepath = $tmp_folder . "/bordereau.pdf";

        $transaction_id = $this->createTransaction(
            ActesStatusSQL::STATUS_ACQUITTEMENT_RECU,
            __DIR__ . "/fixtures/abc-TACT--000000000--20170803-16.tar.gz"
        );

        $this->getObjectInstancier()->set(
            IActesPdf::class,
            new ActesPdfLegacy(SITEROOT . "public.ssl/custom/images/bandeau-s2low-190.jpg")
        );
        $bordereauPdfGenerator = $this->getObjectInstancier()->get(BordereauPdfGenerator::class);

        $this->assertFileDoesNotExist($filepath);
        $bordereauPdfGenerator->generate($transaction_id, $filepath, false, "F");
        $this->assertFileExists($filepath);
        $tmpFolder->delete($tmp_folder);
    }
}
