<?php

namespace S2low\Tests\Services;

use Monolog\Level;
use S2low\Services\PdfValidator;
use S2lowTestCase;
use UnexpectedValueException;

class PdfValidatorTest extends S2lowTestCase
{
    /**
     * @var \S2low\Services\PdfValidator
     */
    private $pdfValidator;

    public function setUp(): void
    {
        parent::setUp();
        $this->pdfValidator = $this->getPdfValidator();
    }

    public function testCheckValidFile()
    {
        $this->assertTrue(
            $this->pdfValidator->check(__DIR__ . "/fixtures/test_pdf.pdf")
        );
    }

    public function testCheckInvalidFile()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("Fichier pdf corrompu : test_pdf_corrupted.pdf");
        $this->pdfValidator->check(__DIR__ . "/fixtures/test_pdf_corrupted.pdf");
    }

    public function testCheckInvalidFileLogs()
    {
        try {
            $this->pdfValidator->check(__DIR__ . "/fixtures/test_pdf_corrupted.pdf");
        } catch (\Exception $exception) {
            //Juste là pour permettre le test après.
        }

        $this->assertTrue(
            $this->testHandler->hasRecord(
                "Fichier pdf corrompu : test_pdf_corrupted.pdf",
                Level::Error
            )
        );
    }

    private function getPdfValidator(): PdfValidator
    {
        return new PdfValidator(
            $this->s2lowLogger
        );
    }
}
