<?php

declare(strict_types=1);

namespace PHPUnit\class;

use DateTime;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use S2low\Services\ProcessCommand\CommandLauncher;
use S2low\Services\ProcessCommand\OpenSSLWrapper;
use S2lowLegacy\Class\VerifyPadesSignature;
use S2lowLegacy\Class\VerifyPemCertificate;
use S2lowLegacy\Lib\PemCertificate;
use S2lowLegacy\Lib\PemCertificateFactory;
use S2lowTestCase;
use stdClass;

class VerifyPadesSignatureTest extends S2lowTestCase
{
    public const RGS_VALIDCA_PATH = __DIR__ . '/../lib/fixtures/validca/';
    private MockObject|VerifyPemCertificate $verifyPemCertificateMock;
    private VerifyPadesSignature $verifyPadesSignatureWithMock;
    private VerifyPadesSignature $verifyPadesSignature;
    private MockObject|PemCertificate $pemCertificateMock;

    private function getSignature(
        bool $valid = true,
        string $signingCert = 'certificat',
        string $signatureDate = '1502268600000'
    ): stdClass|MockObject {
        $signature = $this->getMockBuilder(stdClass::class)
            ->disableOriginalConstructor()
            ->getMock();

        $signature->valid = $valid;
        $signature->signingCert = $signingCert;
        $signature->signatureDate = $signatureDate;

        return $signature;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->verifyPemCertificateMock = $this->getMockBuilder(VerifyPemCertificate::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->pemCertificateMock = $this->getMockBuilder(PemCertificate::class)
            ->disableOriginalConstructor()
            ->getMock();

        $pemCertificateFactoryMock = $this->getMockBuilder(PemCertificateFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $pemCertificateFactoryMock->method('getFromMinimalString')
            ->willReturn($this->pemCertificateMock);

        $this->verifyPadesSignatureWithMock = new VerifyPadesSignature(
            $pemCertificateFactoryMock,
            $this->verifyPemCertificateMock
        );

        $this->verifyPadesSignature = new VerifyPadesSignature(
            new PemCertificateFactory(),
            new VerifyPemCertificate(
                new OpenSSLWrapper(new CommandLauncher())
            )
        );
    }

    /**
     * @throws Exception
     */
    public function testValidateSigned()
    {
        $signature = json_decode(
            file_get_contents(__DIR__ . '/fixtures/signature-pades/return-courrier-signe.json')
        )->signatures[0];

        $this->expectNotToPerformAssertions();
        $this->verifyPadesSignature->validateSignature($signature, self::RGS_VALIDCA_PATH);
    }

    /**
     * @throws Exception
     */
    public function testNotValidateSigned()
    {
        $signature = json_decode(
            file_get_contents(__DIR__ . '/fixtures/signature-pades/return-courrier-alter.json')
        )->signatures[0];
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Au moins une signature n'est pas valide");
        $this->verifyPadesSignature->validateSignature($signature, self::RGS_VALIDCA_PATH);
    }

    /**
     * @throws Exception
     */
    public function testNotValidateAlteredSignature()
    {
        $signature = json_decode(
            file_get_contents(__DIR__ . '/fixtures/signature-pades/return-courrier-alter.json')
        )->signatures[0];
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Au moins une signature n'est pas valide");
        $this->verifyPadesSignature->validateSignature($signature, self::RGS_VALIDCA_PATH);
    }

    //Unit tests
    // checkNecessaryFields

    /**
     * @dataProvider missingNecessaryFieldsProvider
     */

    public function testMissingNecessaryFields($signature, $exceptionMessage)
    {

        $this->expectException(Exception::class);
        $this->expectExceptionMessage($exceptionMessage);
        $this->verifyPadesSignatureWithMock->validateSignature($signature, self::RGS_VALIDCA_PATH);
    }

    public function missingNecessaryFieldsProvider(): array
    {
        return [
            [
                $this->getSignature(false, '', ''),
                "Au moins une signature n'est pas valide"
            ],
            [
                $this->getSignature(true, '', ''),
                'Impossible de récupérer le certificat de signature'
            ],
            [
                $this->getSignature(true, 'certificat', ''),
                'Impossible de determiner la date de la signature'
            ]
        ];
    }

    // checkCertificateWasValidAtSignatureTime

    /**
     * @throws \Exception
     */
    public function testCertificateWasValidOnSignature()
    {
        $this->expectNotToPerformAssertions();
        $this->verifyPadesSignatureWithMock->validateSignature($this->getSignature(), self::RGS_VALIDCA_PATH);
    }

    /**
     * @throws Exception
     */
    public function testCertificateWasInvalidOnSignature()
    {
        $date = new DateTime();
        $date->setTimestamp(1502268600);
        $this->pemCertificateMock
            ->expects(static::once())
            ->method('checkCertificateIsValidAtDate')
            ->with($date);

        $this->verifyPadesSignatureWithMock->validateSignature($this->getSignature(), self::RGS_VALIDCA_PATH);
    }

    /**
     * @throws Exception
     */
    public function testCertificateDateInvalidGoesThrough()
    {
        $this->pemCertificateMock
            ->method('checkCertificateIsValidAtDate')
            ->willThrowException(new Exception('CkSugdE3ETSh9xhQ'));
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('CkSugdE3ETSh9xhQ');
        $this->verifyPadesSignatureWithMock->validateSignature($this->getSignature(), self::RGS_VALIDCA_PATH);
    }

    /**
     * @throws \Exception
     */
    public function testcheckCertificatecheckCertificateWithOpenSSLIsCalled()
    {
        $this->verifyPemCertificateMock
            ->expects(static::once())
            ->method('checkCertificateWithOpenSSL')
            ->with(
                static::stringContains(
                    '/s2low_valid_certifcate_'
                ),
                self::RGS_VALIDCA_PATH,
                static::equalTo(VerifyPemCertificate::CERTIFICATE_CHAIN_ERRORS)
            );

        $this->verifyPadesSignatureWithMock->validateSignature($this->getSignature(), self::RGS_VALIDCA_PATH);
    }

    public function testcheckCertificateWithOpenSSLExceptionGoesThrough()
    {
        $this->verifyPemCertificateMock
            ->method('checkCertificateWithOpenSSL')
            ->willThrowException(new Exception('Exception de test LahgnjCM'));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Exception de test LahgnjCM');
        $this->verifyPadesSignatureWithMock->validateSignature($this->getSignature(), self::RGS_VALIDCA_PATH);
    }
}
