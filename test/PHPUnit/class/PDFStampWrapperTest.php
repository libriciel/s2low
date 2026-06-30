<?php

use S2lowLegacy\Class\CurlWrapper;
use S2lowLegacy\Class\CurlWrapperFactory;
use S2lowLegacy\Class\PDFStampData;
use S2lowLegacy\Class\PDFStampWrapper;

class PDFStampWrapperTest extends PHPUnit_Framework_TestCase
{
    private function getCurlWrapperFactory($return_string)
    {
        $curlWrapper = $this->getMockBuilder(CurlWrapper::class)->getMock();
        $curlWrapper->method("get")->willReturn($return_string);
        $curlWrapperFactory = $this->getMockBuilder(CurlWrapperFactory::class)->getMock();
        $curlWrapperFactory->method("getNewInstance")->willReturn($curlWrapper);
        /** @var CurlWrapperFactory $curlWrapperFactory */
        return $curlWrapperFactory;
    }

    /**
     * @throws Exception
     */
    public function testStamp()
    {
        $pdfStampWrapper = new PDFStampWrapper(
            "http://pdf-stamp/",
            __DIR__ . "/../../../public.ssl/custom/images/s2low-stamp.png",
            new \S2lowLegacy\Class\PdfStampMessages(false)
        );
        $pdfStampWrapper->setCurlWrapperFactory($this->getCurlWrapperFactory("test"));
        $pdfStampData = new PDFStampData();
        $pdfStampData->identifiant_unique = "toto";
        $pdfStampData->affichage_date = "2017-09-18";
        $pdfStampData->envoi_prefecture_date = "2017-09-15";
        $pdfStampData->recu_prefecture_date = "2017-09-16";

        $this->assertEquals(
            "test",
            $pdfStampWrapper->stamp(__DIR__ . "/fixtures/signature-pades/Courrier.pdf", $pdfStampData)
        );
    }

    public function testPdfStampUrlIsCorrect()
    {
        $this->assertTrue(defined('PDF_STAMP_URL'), "PDF_STAMP_URL constant must be defined");
        $this->assertStringStartsWith('http://', PDF_STAMP_URL, "PDF_STAMP_URL must start with http:// or https://");
        $this->assertStringContainsString('/pdf-stamp/', PDF_STAMP_URL, "PDF_STAMP_URL must contain the /pdf-stamp/ context path");
    }

    public function testPdfStampIsReachable()
    {
        if (!defined('PDF_STAMP_URL') || strpos(PDF_STAMP_URL, 'pdf-stamp') === false) {
            $this->markTestSkipped("PDF_STAMP_URL is not configured for a live docker network test.");
        }

        $curlWrapper = new CurlWrapper();
        $curlWrapper->get(PDF_STAMP_URL);

        $this->assertEquals(
            405,
            $curlWrapper->getLastHttpCode(),
            "PDF stamp service is not reachable or did not return HTTP 405 (Method Not Allowed) on GET. Output: " . $curlWrapper->getLastOutput()
        );
    }
}
