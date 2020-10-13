<?php

class VerifyPKCS7SignatureTest extends S2lowTestCase
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
                __DIR__ . "/fixtures/signaturesPKCS7/test_pdf.pdf",
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
            __DIR__ . "/fixtures/signaturesPKCS7/test_pdf.pdf",
            file_get_contents(__DIR__ . "/fixtures/signaturesPKCS7/test_pdf.pdf.p7s")
        );
    }

    // Test des dates du certificat
    // fullchain.pem
    //      notBefore=Jun 12 14:00:58 2020 GMT
    //      notAfter=Jun 10 14:00:58 2030 GMT
    // myCA.pem
    //      notBefore=Jun 12 14:00:56 2020 GMT
    //      notAfter=Jun 11 14:00:56 2025 GMT
    // L'EXPIRATION DES CRLS N'EST PAS PRISE EN COMPTE !
    // crl.pem
    //      lastUpdate=Jan 26 15:00:58 2021 GMT
    //      nextUpdate=Jan 24 15:00:58 2031 GMT

    /**
     * @dataProvider getWrongDate
     */

    public function testWrongDateIsTakenIntoAccount(DateTime $dateTime, string $message)
    {
        $verifyPKCS7Signature = new VerifyPKCS7Signature(
            __DIR__ . "/fixtures/signaturesPKCS7/ac",
            new VerifyPemCertificateFactory(),
            new PemCertificateFactory()
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage($message);
        $verifyPKCS7Signature->verify(
            __DIR__ . "/fixtures/signaturesPKCS7/test_pdf.pdf",
            file_get_contents(__DIR__ . "/fixtures/signaturesPKCS7/test_pdf.pdf.p7s"),
            $dateTime
        );
    }

    public function getWrongDate(): array
    {
        return [
            [new DateTime("Jun 12 14:00:57 2020", new DateTimeZone("GMT")),
                "La date de la signature 12-Jun-2020 14:00:57 n'entre pas dans la date de validité du certificat 12-Jun-2020 16:00:58"
            ],
            [new DateTime("Jun 10 14:00:59 2030", new DateTimeZone("GMT")),
                "La date de la signature 10-Jun-2030 14:00:59 n'entre pas dans la date de validité du certificat 12-Jun-2020 16:00:58"
            ]
        ];
    }

    /**
     * @dataProvider getGoodDate
     */

    public function testGoodDateIsTakenIntoAccount(DateTime $dateTime)
    {
        $verifyPKCS7Signature = new VerifyPKCS7Signature(
            __DIR__ . "/fixtures/signaturesPKCS7/ac",
            new VerifyPemCertificateFactory(),
            new PemCertificateFactory()
        );

        $this->assertTrue(
            $verifyPKCS7Signature->verify(
                __DIR__ . "/fixtures/signaturesPKCS7/test_pdf.pdf",
                file_get_contents(__DIR__ . "/fixtures/signaturesPKCS7/test_pdf.pdf.p7s"),
                $dateTime
            )
        );
    }

    public function getGoodDate(): array
    {
        return [
            [new DateTime("Jan 26 15:00:58 2021", new DateTimeZone("GMT"))],// Debut de validité crl
            [new DateTime("Jun 11 14:00:55 2025", new DateTimeZone("GMT"))] // Fin de validité myCA.pem
        ];
    }

    /** @var  $openSslWrapper OpenSslWrapper | MockObject */
    private  $openSslWrapper;

    public function setUp(): void
    {
        parent::setUp();
        /** @var  $openSslWrapper OpenSslWrapper | MockObject */
        $this->openSslWrapper = $this->getMockBuilder(OpenSslWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testCheckCertificateAutosigneDateOk(){

        $cmd = "test";
        $out = [ "CN = example.com",
                "error 18 at 0 depth lookup: self signed certificate",
                "CN = example.com",
                "error 3 at 0 depth lookup: unable to get certificate CRL",
                "error example.crt: verification failed"];

        $ret = 2;

        $this->openSslWrapper->method("verifyCertificate")->willReturn([$cmd,$out,$ret,"d"]);

        $verifyPKCS7Signature = new VerifyPKCS7Signature("/a/b/c/",$this->openSslWrapper);

        $this->assertTrue($verifyPKCS7Signature->checkCertificate("a"));

    }

    public function testCheckCertificateAutosigneDateKo(){
        $cmd = "test";
        $out = [ "CN = example.com",
                "error 18 at 0 depth lookup: self signed certificate",
                "CN = example.com",
                "error 3 at 0 depth lookup: unable to get certificate CRL",
                "error example.crt: verification failed"];
        $ret = 2;

        $this->expectException(Exception::class);
        $this->openSslWrapper->method("verifyCertificate")->willReturn([$cmd,$out,$ret,"d"]);
        $verifyPKCS7Signature = new VerifyPKCS7Signature("/a/b/c/",$this->openSslWrapper);
        $verifyPKCS7Signature->checkCertificate("a");
        //$this->expectExceptionMessage();
        //$this->assertTrue(false);
    }

    public function testCheckCertificateRGSDateOk(){
        $cmd = "test";
        $out = [ "fullchain.pem: OK" ];
        $ret = 0;

        $this->openSslWrapper->method("verifyCertificate")->willReturn([$cmd,$out,$ret,"d"]);
        $verifyPKCS7Signature = new VerifyPKCS7Signature("/a/b/c/",$this->openSslWrapper);
        $this->assertTrue($verifyPKCS7Signature->checkCertificate("a"));
    }

    public function testCheckCertificateRGSDateKO(){
        $cmd = "test";
        $out = [ "C = FR, ST = HERAULT, L = MONTPELLIER, O = LIBRICIEL, OU = CA_POUR_TEST, CN = DOCKER CA, emailAddress = test@localhost",
                "error 10 at 1 depth lookup: certificate has expired",
                "C = FR, ST = HERAULT, L = MONTPELLIER, O = LIBRICIEL, OU = CERTIFICAT_POUR_TESTS, CN = test.fr, emailAddress = test@localhost",
                "error 10 at 0 depth lookup: certificate has expired",
                "error fullchain.pem: verification failed"];
        $ret = 2;

        $this->expectException(Exception::class);
        $this->openSslWrapper->method("verifyCertificate")->willReturn([$cmd,$out,$ret,"d"]);
        $verifyPKCS7Signature = new VerifyPKCS7Signature("/a/b/c/",$this->openSslWrapper);
        $verifyPKCS7Signature->checkCertificate("a");
    }

    public function testCheckCertificateNoValidCertChainDateOk(){
        $cmd = "truc";
        $out = ["C = FR, ST = HERAULT, L = MONTPELLIER, O = LIBRICIEL, OU = CERTIFICAT_POUR_TESTS, CN = test.fr, emailAddress = test@localhost",
                "error 20 at 0 depth lookup: unable to get local issuer certificate",
                "error fullchain.pem: verification failed"];
        $ret = 2;

        $this->openSslWrapper->method("verifyCertificate")->willReturn([$cmd,$out,$ret,"d"]);
        $verifyPKCS7Signature = new VerifyPKCS7Signature("/a/b/c/",$this->openSslWrapper);
        $this->assertTrue($verifyPKCS7Signature->checkCertificate("a"));
    }

    public function testCheckCertificateNoValidCertChainDateKo(){
        $cmd = "truc";
        $out = [ "C = FR, ST = HERAULT, L = MONTPELLIER, O = LIBRICIEL, OU = CERTIFICAT_POUR_TESTS, CN = test.fr, emailAddress = test@localhost",
                "error 20 at 0 depth lookup: unable to get local issuer certificate",
                "error fullchain.pem: verification failed"];
        $ret = 2;

        $this->expectException(Exception::class);
        $this->openSslWrapper->method("verifyCertificate")->willReturn([$cmd,$out,$ret,"d"]);
        $verifyPKCS7Signature = new VerifyPKCS7Signature("/a/b/c/",$this->openSslWrapper);
        $verifyPKCS7Signature->checkCertificate("a");
    }

    public function testCheckCertificateRGSDateOkCRLOk(){
        //TODO
        $this->assertTrue(false);
    }

    public function testCheckCertificateRGSCRLKO(){
        //TODO
        $this->assertTrue(false);
    }
}