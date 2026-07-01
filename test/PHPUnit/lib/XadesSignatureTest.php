<?php

declare(strict_types=1);

use S2lowLegacy\Class\VerifyPemCertificate;
use S2lowLegacy\Lib\PemCertificateFactory;
use S2lowLegacy\Lib\XadesSignature;
use S2lowLegacy\Lib\XadesSignatureParser;

class XadesSignatureTest extends S2lowTestCase
{
    private function getXadesSignature(): XadesSignature
    {
        $xadesSignature = new XadesSignature(
            XMLSEC1_PATH,
            __DIR__ . '/fixtures/validca_for_xades/',
            new XadesSignatureParser(),
            new PemCertificateFactory(),
            $this->getObjectInstancier()->get(VerifyPemCertificate::class)
        );
        return $xadesSignature;
    }

    public function filesProvider(): Generator
    {
        yield "with minimal file" => [__DIR__ . "/fixtures/test.xml", false];
        yield "with PES file" => [__DIR__ . "/fixtures/HELIOS_SIMU_ALR2_1444811220_681372666.xml", true];
    }

    private function verify($file_to_verify)
    {
        $xadesSignature = $this->getXadesSignature();
        $xadesSignature->verify($file_to_verify);
        $this->assertTrue(true); //test no exception is thrown;
    }

    public function testVerifyNOCA()
    {
        $xadesSignature = $this->getXadesSignature();
        $xadesSignature->verify(__DIR__ . "/fixtures/HELIOS_SIMU_ALR2_1445334258_694103934.xml");
        $this->assertTrue(true);    //Test no exception is thrown
    }

    public function testVerifSignatureNotGlobale()
    {
        $this->verify(__DIR__ . "/fixtures/signature_bordereau2.xml");
    }

    public function testVerifSignatureNotGlobaleBad()
    {
        $xadesSignature = $this->getXadesSignature();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Impossible d'affirmer que la signature correspond au fichier");
        $xadesSignature->verify(__DIR__ . "/fixtures/signature_bordereau_bad.xml");
    }

    public function testDeleteSignature()
    {
        $file = __DIR__ . "/fixtures/HELIOS_SIMU_ALR2_1445334258_694103934.xml";

        $result = "/tmp/result.xml";
        $xadesSignature = $this->getXadesSignature();
        $this->assertTrue($xadesSignature->isSigned($file));
        $xadesSignature->deleteSignature($file, $result);
        $this->assertFalse($xadesSignature->isSigned($result));
    }

    /** @dataProvider datesProvider */

    public function testDateEffect(DateTime $dateTime, bool $expected)
    {
        $xadesSignatureParser = $this->getMockBuilder(XadesSignatureParser::class)
            ->disableOriginalConstructor()
            ->getMock();

        $xadesSignatureParser->method("extractXadesSigningTime")
            ->willReturn($dateTime);
        $xadesSignature = new XadesSignature(
            XMLSEC1_PATH,
            __DIR__ . "/fixtures/validca_for_xades/",
            $xadesSignatureParser,
            new PemCertificateFactory(),
            $this->getObjectInstancier()->get(VerifyPemCertificate::class)
        );

        $verify = true;
        try {
            $xadesSignature->verify(__DIR__ . "/fixtures/signature_bordereau.xml");
        } catch (Exception $e) {
            $verify = false;
        }
        $this->assertEquals(
            $expected,
            $verify
        );
    }

    public function datesProvider()
    {
        return [
            // Date de vérification                  validité attendue
            [new DateTime("2012-11-05T11:33:13Z"), false],    // Limite basse du certificat AC_ADULLACT_ROOT_G3
            [new DateTime("2016-11-07T11:03:01Z"), true],     // Date de la signature
            [new DateTime("2022-11-05T11:33:15Z"), false],     // Limite haute du certificat AC_ADULLACT_ROOT_G3
        ];
    }

    public function testcheckCertificateWithOpenSSLThrowException()
    {
        $xadesSignatureParser = $this->getMockBuilder(XadesSignatureParser::class)
            ->disableOriginalConstructor()
            ->getMock();

        $verifyPemCertificate = $this->getMockBuilder(VerifyPemCertificate::class)
            ->disableOriginalConstructor()
            ->getMock();

        $verifyPemCertificate->method("checkCertificateWithOpenSSL")
            ->willThrowException(new Exception("Test"));

        $xadesSignature = new XadesSignature(
            XMLSEC1_PATH,
            __DIR__ . "/fixtures/validca_for_xades/",
            $xadesSignatureParser,
            new PemCertificateFactory(),
            $verifyPemCertificate
        );

        $filesBeforeVerify = glob('/tmp/s2low_xades_*');
        try {
            $xadesSignature->verify(__DIR__ . "/fixtures/signature_bordereau.xml");
        } catch (Exception $e) {
            $filesAfterVerify = glob('/tmp/s2low_xades_*');
            $this->assertSameSize(
                $filesAfterVerify,
                $filesBeforeVerify
            );
        }
    }
}
