<?php

class verifyPKCS7SignatureTest extends S2lowTestCase
{
    public function testRightFileWithSignature()
    {
        $verifyPKCS7Signature = new VerifyPKCS7Signature(
            __DIR__ . "/fixtures/signaturesPKCS7/ac",
            new VerifyPemCertificateFactory(),
            new PemCertificateFactory()
        );

        $this->assertTrue(
            $verifyPKCS7Signature->verify(
                __DIR__ . "fixtures/signaturesPKCS7/test_pdf.pdf",
                file_get_contents(__DIR__ . "/fixtures/signaturesPKCS7/test_pdf.pdf.p7s")
            )
        );
    }

    public function testWrongFileWithSignature()
    {
        $verifyPKCS7Signature = new VerifyPKCS7Signature(
            __DIR__ . "/fixtures/signaturesPKCS7/ac",
            new VerifyPemCertificateFactory(),
            new PemCertificateFactory()
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
        $verifyPKCS7Signature = new VerifyPKCS7Signature(
            __DIR__ . "/",
            new VerifyPemCertificateFactory(),
            new PemCertificateFactory()
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(" unable to get local issuer certificate");
        $verifyPKCS7Signature->verify(
            __DIR__ . "fixtures/signaturesPKCS7/test_pdf.pdf",
            file_get_contents(__DIR__ . "/fixtures/signaturesPKCS7/test_pdf.pdf.p7s")
        );
    }
}