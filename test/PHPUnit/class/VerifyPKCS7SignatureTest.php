<?php

class verifyPKCS7SignatureTest extends S2lowTestCase
{

    public function testRightFileWithSignature()
    {
        $verifyPemCertificateFactory = new VerifyPemCertificateFactory();

        $verifyPKCS7Signature = new VerifyPKCS7Signature(
            __DIR__ . "/fixtures/signaturesPKCS7/ac",
            $verifyPemCertificateFactory
        );

        //$this->expectNotToPerformAssertions();
        $this->assertTrue(
            $verifyPKCS7Signature->verify(
                __DIR__ . "fixtures/signaturesPKCS7/test_pdf.pdf",
                file_get_contents(__DIR__ . "/fixtures/signaturesPKCS7/test_pdf.pdf.p7s")
            )
        );
    }

    public function testWrongFileWithSignature()
    {
        $verifyPemCertificateFactory = new VerifyPemCertificateFactory();

        $verifyPKCS7Signature = new VerifyPKCS7Signature(
            __DIR__ . "/fixtures/signaturesPKCS7/ac",
            $verifyPemCertificateFactory
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("La vérification de la signature a échoué");
        $verifyPKCS7Signature->verify(
            __DIR__ . "/fixtures/toto.txt",
            file_get_contents(__DIR__ . "/fixtures/signaturesPKCS7/test_pdf.pdf.p7s")
        );
    }

    public function testRightFileWithWrongAC()
    {
        $verifyPemCertificateFactory = new VerifyPemCertificateFactory();

        $verifyPKCS7Signature = new VerifyPKCS7Signature(
            __DIR__ . "/",
            $verifyPemCertificateFactory
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(" unable to get local issuer certificate");
        $verifyPKCS7Signature->verify(
            __DIR__ . "fixtures/signaturesPKCS7/test_pdf.pdf",
            file_get_contents(__DIR__ . "/fixtures/signaturesPKCS7/test_pdf.pdf.p7s")
        );
    }
}