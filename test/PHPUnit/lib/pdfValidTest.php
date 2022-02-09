<?php

class pdfValidTest extends S2lowTestCase
{
    public function testCheckValidFile(){
        $pdfValid = new PdfValid();
        $this->assertTrue($pdfValid->check(__DIR__."/fixtures/test_pdf.pdf"));
    }

    public function testCheckInvalidFile(){
        $pdfValid = new PdfValid();
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("Fichier pdf corrompu dans l'archive : test_pdf_corrupted.pdf");
        $this->assertTrue($pdfValid->check(__DIR__."/fixtures/test_pdf_corrupted.pdf"));
    }

}