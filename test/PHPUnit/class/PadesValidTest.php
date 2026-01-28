<?php

declare(strict_types=1);

namespace PHPUnit\class;

use Exception;
use S2lowLegacy\Class\CurlWrapper;
use S2lowLegacy\Class\CurlWrapperFactory;
use S2lowLegacy\Class\PadesValid;
use S2lowLegacy\Class\RecoverableException;
use S2lowLegacy\Class\VerifyPadesSignature;
use S2lowTestCase;

class PadesValidTest extends S2lowTestCase
{
    /**
     * @param string $returnString
     * @param string $lastHttpCode
     * @param string $lastError
     * @param string $lastOutput
     * @return PadesValid
     */

    private function createPadesValidForExceptions(string $returnString, string $lastHttpCode, string $lastError, string $lastOutput): PadesValid
    {
        $curlWrapperMock = $this->getMockBuilder(CurlWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $curlWrapperMock->method('get')
            ->willReturn($returnString);
        $curlWrapperMock->method('getLastHttpCode')
            ->willReturn($lastHttpCode);
        $curlWrapperMock->method('getLastError')
            ->willReturn($lastError);
        $curlWrapperMock->method('getLastOutput')
            ->willReturn($lastOutput);

        $curlWrapperFactoryMock = $this->getMockBuilder(CurlWrapperFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $curlWrapperFactoryMock->method('getNewInstance')
            ->willReturn($curlWrapperMock);

        $verifyPadesSignatureMock = $this->getMockBuilder(VerifyPadesSignature::class)
            ->disableOriginalConstructor()
            ->getMock();

        return new PadesValid('bli', $curlWrapperFactoryMock, $verifyPadesSignatureMock);
    }

    private function createPadesValidForValidation(
        ?string $exceptionMessage = null,
    ): PadesValid {
        $returnString = '{"signatures":["une signature"],"signed":true}';

        $curlWrapperMock = $this->getMockBuilder(CurlWrapper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $curlWrapperMock->method('get')
            ->willReturn($returnString);

        $curlWrapperFactoryMock = $this->getMockBuilder(CurlWrapperFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $curlWrapperFactoryMock->method('getNewInstance')
            ->willReturn($curlWrapperMock);

        $verifyPadesSignatureMock = $this->getMockBuilder(VerifyPadesSignature::class)
            ->disableOriginalConstructor()
            ->getMock();

        if (! is_null($exceptionMessage)) {
            $verifyPadesSignatureMock->expects(
                static::exactly(1)
            )->method('validateSignature')->willThrowException(
                new Exception($exceptionMessage)
            )->with('une signature');
        } else {
            $verifyPadesSignatureMock->expects(
                static::exactly(1)
            )->method('validateSignature')
                ->with('une signature');
        }

        return new PadesValid('bli', $curlWrapperFactoryMock, $verifyPadesSignatureMock);
    }

    /**
     * @throws Exception
     */
    public function testValidateNotSigned()
    {

        $returnString = '{"signatures":[],"signed":false}';

        $lastError = '';
        $lastOutput = '';
        $lastHttpCode = '';

        $padesValid = $this->createPadesValidForExceptions($returnString, $lastHttpCode, $lastError, $lastOutput);

        static::assertFalse(
            $padesValid->validate(
                __DIR__ . '/fixtures/signature-pades/Courrier.pdf',
                'certificateStorePath'
            )
        );
    }

    /**
     * @dataProvider provider
     * @throws RecoverableException
     */
    public function testgetPadesValidResultExceptions(
        $returnString,
        $lastError,
        $lastOutput,
        $lastHttpCode,
        $exceptionClass,
        $exceptionMessage
    ) {

        $padesValid = $this->createPadesValidForExceptions(
            $returnString,
            $lastHttpCode,
            $lastError,
            $lastOutput
        );

        $this->expectException($exceptionClass);
        $this->expectExceptionMessage($exceptionMessage);
        $padesValid->validate(
            __DIR__ . '/fixtures/signature-pades/Courrier.pdf',
            'certificateStorePath'
        );
    }

    public function provider(): array
    {
        return[
            ['{"signatures":[],"signed":true}', '', '', '',Exception::class, 'Impossible de determiner si le fichier est signé'],
            ['{"signatures":[]}', '', '', '',Exception::class, 'Impossible de determiner si le fichier est signé'],
            ['', 'last error', 'last output', '404',Exception::class, 'last error last output'],
            ['', 'last error', 'last output', '',RecoverableException::class, 'last error last output'],
            ['uzye', '', '', '',Exception::class, 'Impossible de décoder le message de pades-valid : '],


        ];
    }

    /**
     * @throws RecoverableException
     */
    public function testvalidate()
    {
        $padesValid = $this->createPadesValidForValidation();

        static::assertTrue(
            $padesValid->validate(
                '/vers/un/fichier',
                'certificateStorePath'
            )
        );
    }

    /**
     * @throws \S2lowLegacy\Class\RecoverableException
     */
    public function testValidateCertificateChecking()
    {
        $padesValid = $this->createPadesValidForValidation();

        static::assertTrue(
            $padesValid->validate(
                '/vers/un/fichier',
                'certificateStorePath'
            )
        );
    }

    /**
     * @throws \S2lowLegacy\Class\RecoverableException
     */
    public function testWithoutCertificateChecking()
    {
        $padesValid = $this->createPadesValidForValidation();

        static::assertTrue(
            $padesValid->validate(
                '/vers/un/fichier',
                'certificateStorePath'
            )
        );
    }

    /**
     * @throws \S2lowLegacy\Class\RecoverableException
     */
    public function testExceptionThrowGetsThrough()
    {
        $padesValid = $this->createPadesValidForValidation(
            'Une Exception'
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Une Exception');

        $padesValid->validate(
            '/vers/un/fichier',
            'certificateStorePath'
        );
    }
}
