<?php

use S2lowLegacy\Class\CurlWrapper;
use S2lowLegacy\Class\CurlWrapperFactory;
use S2lowLegacy\Class\PDFStampData;
use S2lowLegacy\Class\PDFStampWrapper;

class PDFStampWrapperTest extends PHPUnit_Framework_TestCase
{
    private $postFile = [];
    private $postData = [];
    private $calledUrl;
    private $lastOutput = '';

    private function getCurlWrapperFactory($return_string)
    {
        $curlWrapper = $this->getMockBuilder(CurlWrapper::class)->getMock();

        $curlWrapper->method("addPostFile")->willReturnCallback(
            function ($field, $filePath, $fileName = false, $contentType = "application/octet-stream") {
                $this->postFile[$field] = [
                    'path' => $filePath,
                    'fileName' => $fileName,
                    'contentType' => $contentType,
                ];
            }
        );

        $curlWrapper->method("addPostData")->willReturnCallback(
            function ($name, $value) {
                $this->postData[$name] = $value;
            }
        );

        $curlWrapper->method("get")->willReturnCallback(
            function ($url) use ($return_string) {
                $this->calledUrl = $url;
                return $return_string;
            }
        );

        $curlWrapper->method("getLastOutput")->willReturnCallback(fn() => $this->lastOutput);
        $curlWrapper->method("getLastError")->willReturn("Erreur HTTP : Code 400");

        $curlWrapperFactory = $this->getMockBuilder(CurlWrapperFactory::class)->getMock();
        $curlWrapperFactory->method("getNewInstance")->willReturn($curlWrapper);
        /** @var CurlWrapperFactory $curlWrapperFactory */
        return $curlWrapperFactory;
    }

    private function getPdfStampWrapper($return_string, $pdf_stamp_url = "http://pdf-stamp:8080")
    {
        $pdfStampWrapper = new PDFStampWrapper(
            $pdf_stamp_url,
            __DIR__ . "/../../../public.ssl/custom/images/s2low-stamp.png",
            new \S2lowLegacy\Class\PdfStampMessages(false)
        );
        $pdfStampWrapper->setCurlWrapperFactory($this->getCurlWrapperFactory($return_string));
        return $pdfStampWrapper;
    }

    private function getPdfStampData()
    {
        $pdfStampData = new PDFStampData();
        $pdfStampData->identifiant_unique = "toto";
        $pdfStampData->affichage_date = "2017-09-18";
        $pdfStampData->envoi_prefecture_date = "2017-09-15";
        $pdfStampData->recu_prefecture_date = "2017-09-16";
        return $pdfStampData;
    }

    /**
     * @throws Exception
     */
    public function testStamp()
    {
        $pdfStampWrapper = $this->getPdfStampWrapper("test");

        $this->assertEquals(
            "test",
            $pdfStampWrapper->stamp(__DIR__ . "/fixtures/signature-pades/Courrier.pdf", $this->getPdfStampData())
        );
    }

    /**
     * @throws Exception
     */
    public function testStampCallsV3Endpoint()
    {
        $pdfStampWrapper = $this->getPdfStampWrapper("test", "http://pdf-stamp:8080/");
        $pdfStampWrapper->stamp(__DIR__ . "/fixtures/signature-pades/Courrier.pdf", $this->getPdfStampData());

        $this->assertEquals("http://pdf-stamp:8080/pdf-stamp/v3/stamp/add", $this->calledUrl);
    }

    /**
     * La partie JSON doit être envoyée en application/json : pdf-stamp répond 415 sinon.
     *
     * @throws Exception
     */
    public function testStampRequestIsSentAsJsonPart()
    {
        $pdfStampWrapper = $this->getPdfStampWrapper("test");
        $pdfStampWrapper->stamp(__DIR__ . "/fixtures/signature-pades/Courrier.pdf", $this->getPdfStampData());

        $this->assertArrayHasKey('stampRequest', $this->postData);
        $this->assertInstanceOf(CURLStringFile::class, $this->postData['stampRequest']);
        $this->assertEquals('application/json', $this->postData['stampRequest']->mime);
    }

    /**
     * @throws Exception
     */
    public function testStampRequestContent()
    {
        $pdfStampWrapper = $this->getPdfStampWrapper("test");
        $pdfStampWrapper->stamp(__DIR__ . "/fixtures/signature-pades/Courrier.pdf", $this->getPdfStampData());

        $stampRequest = json_decode($this->postData['stampRequest']->data, true);

        $this->assertCount(1, $stampRequest['stampList']);
        $stamp = $stampRequest['stampList'][0];

        $this->assertEquals('TOP_RIGHT', $stamp['position']['origin']);
        $this->assertEquals(
            ['width' => 190, 'height' => 55, 'x' => 10, 'y' => 10, 'origin' => 'TOP_RIGHT'],
            $stamp['position']
        );
        $this->assertEquals(
            [
                ['title' => 'Envoi simulé le', 'value' => '15/09/2017'],
                ['title' => 'Reception simulée le', 'value' => '16/09/2017'],
                [
                    'title' => 'Publication simulée le',
                    'value' => '18/09/2017',
                    'logo' => ['imageRef' => 's2low-stamp.png', 'width' => 60, 'marginRight' => 20],
                ],
                ['title' => 'ID :', 'value' => 'toto'],
            ],
            $stamp['rows']
        );
    }

    /**
     * Le logo est transmis par la partie multipart « images », référencée par imageRef.
     *
     * @throws Exception
     */
    public function testLogoIsSentAsImagePart()
    {
        $pdfStampWrapper = $this->getPdfStampWrapper("test");
        $pdfStampWrapper->stamp(__DIR__ . "/fixtures/signature-pades/Courrier.pdf", $this->getPdfStampData());

        $this->assertArrayHasKey('pdfSource', $this->postFile);
        $this->assertArrayHasKey('images', $this->postFile);
        $this->assertEquals('s2low-stamp.png', $this->postFile['images']['fileName']);

        $stampRequest = json_decode($this->postData['stampRequest']->data, true);
        $this->assertEquals(
            $this->postFile['images']['fileName'],
            $stampRequest['stampList'][0]['rows'][2]['logo']['imageRef']
        );
    }

    /**
     * En erreur, l'exception reprend telle quelle la dernière erreur et la sortie brute de curl,
     * comme avant la migration vers l'API v3.
     */
    public function testStampThrowsWithLastErrorAndRawOutput()
    {
        $pdfStampWrapper = $this->getPdfStampWrapper(false);
        $this->lastOutput = '{"status":415,"detail":"Content-Type is not supported."}';

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            'Erreur HTTP : Code 400 {"status":415,"detail":"Content-Type is not supported."}'
        );

        $pdfStampWrapper->stamp(__DIR__ . "/fixtures/signature-pades/Courrier.pdf", $this->getPdfStampData());
    }
}
