<?php

use S2lowLegacy\Class\CurlWrapper;
use S2lowLegacy\Class\CurlWrapperFactory;
use S2lowLegacy\Class\PDFStampData;
use S2lowLegacy\Class\PDFStampWrapper;
use S2lowLegacy\Class\PdfStampMessages;

class PDFStampWrapperTest extends PHPUnit_Framework_TestCase
{
    private array $postFile = [];
    private array $postData = [];
    private string $calledUrl = '';
    private string $lastOutput = '';

    private function getCurlWrapperFactory(string|false $curlResponse): CurlWrapperFactory
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
            function ($url) use ($curlResponse) {
                $this->calledUrl = $url;
                return $curlResponse;
            }
        );

        $curlWrapper->method("getLastOutput")->willReturnCallback(fn() => $this->lastOutput);
        $curlWrapper->method("getLastError")->willReturn("Erreur HTTP : Code 400");

        $curlWrapperFactory = $this->getMockBuilder(CurlWrapperFactory::class)->getMock();
        $curlWrapperFactory->method("getNewInstance")->willReturn($curlWrapper);
        return $curlWrapperFactory;
    }

    private function getPdfStampWrapper(
        string|false $curlResponse,
        string $pdfStampUrl = "http://pdf-stamp:8080"
    ): PDFStampWrapper {
        $pdfStampWrapper = new PDFStampWrapper(
            $pdfStampUrl,
            __DIR__ . "/../../../public.ssl/custom/images/s2low-stamp.png",
            new PdfStampMessages(false)
        );
        $pdfStampWrapper->setCurlWrapperFactory($this->getCurlWrapperFactory($curlResponse));
        return $pdfStampWrapper;
    }

    private function getPdfStampData(): PDFStampData
    {
        $pdfStampData = new PDFStampData();
        $pdfStampData->identifiant_unique = "toto";
        $pdfStampData->affichage_date = "2017-09-18";
        $pdfStampData->envoi_prefecture_date = "2017-09-15";
        $pdfStampData->recu_prefecture_date = "2017-09-16";
        return $pdfStampData;
    }

    private function stamp(PDFStampWrapper $pdfStampWrapper): string
    {
        return $pdfStampWrapper->stamp(
            __DIR__ . "/fixtures/signature-pades/Courrier.pdf",
            $this->getPdfStampData()
        );
    }

    private function getStampRequest(): array
    {
        return json_decode($this->postData['stampRequest']->data, true);
    }

    public function testStamp(): void
    {
        $this->assertSame("test", $this->stamp($this->getPdfStampWrapper("test")));
    }

    public function testStampCallsV3Endpoint(): void
    {
        $this->stamp($this->getPdfStampWrapper("test", "http://pdf-stamp:8080/"));

        $this->assertSame("http://pdf-stamp:8080/pdf-stamp/v3/stamp/add", $this->calledUrl);
    }

    public function testStampRequestIsSentAsJsonPart(): void
    {
        $this->stamp($this->getPdfStampWrapper("test"));

        $this->assertArrayHasKey('stampRequest', $this->postData);
        $this->assertInstanceOf(CURLStringFile::class, $this->postData['stampRequest']);
        $this->assertSame('application/json', $this->postData['stampRequest']->mime);
    }

    public function testStampRequestContent(): void
    {
        $this->stamp($this->getPdfStampWrapper("test"));

        $stampRequest = $this->getStampRequest();

        $this->assertCount(1, $stampRequest['stampList']);
        $stamp = $stampRequest['stampList'][0];

        $this->assertSame(
            ['width' => 190, 'height' => 55, 'x' => 10, 'y' => 10, 'origin' => 'TOP_RIGHT'],
            $stamp['position']
        );
        $this->assertSame(
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

    public function testLogoIsSentAsImagePart(): void
    {
        $this->stamp($this->getPdfStampWrapper("test"));

        $this->assertArrayHasKey('pdfSource', $this->postFile);
        $this->assertArrayHasKey('images', $this->postFile);
        $this->assertSame('s2low-stamp.png', $this->postFile['images']['fileName']);
        $this->assertSame(
            $this->postFile['images']['fileName'],
            $this->getStampRequest()['stampList'][0]['rows'][2]['logo']['imageRef']
        );
    }

    public function testStampThrowsWithLastErrorAndRawOutput(): void
    {
        $pdfStampWrapper = $this->getPdfStampWrapper(false);
        $this->lastOutput = '{"status":415,"detail":"Content-Type is not supported."}';

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            'Erreur HTTP : Code 400 {"status":415,"detail":"Content-Type is not supported."}'
        );

        $this->stamp($pdfStampWrapper);
    }
}
