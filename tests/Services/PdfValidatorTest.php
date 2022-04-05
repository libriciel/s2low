<?php

namespace S2low\Tests\Services;

use S2low\Services\PdfValidator;
use S2lowTestCase;
use UnexpectedValueException;

class PdfValidatorTest extends S2lowTestCase
{
    public function testCheckValidFile()
    {
        $pdfValid = new PdfValidator(
            $this->getMockBuilder(\S2lowLogger::class)->disableOriginalConstructor()->getMock()
        );
        $this->assertTrue($pdfValid->check(__DIR__ . "/fixtures/test_pdf.pdf"));
    }

    public function testCheckInvalidFile()
    {
        $pdfValid = new PdfValidator(
            $this->getMockBuilder(\S2lowLogger::class)->disableOriginalConstructor()->getMock()
        );
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("Fichier pdf corrompu dans l'archive : test_pdf_corrupted.pdf");
        $this->assertTrue($pdfValid->check(__DIR__ . "/fixtures/test_pdf_corrupted.pdf"));
    }

}