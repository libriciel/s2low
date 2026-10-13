<?php

declare(strict_types=1);

namespace S2low\Tests\Services\Certificates;

use DateTime;
use PHPUnit\Framework\TestCase;
use S2low\Services\ProcessCommand\CheckSnInCRLCommandOutputTranslator;
use S2lowLegacy\Class\RecoverableException;
use Symfony\Component\Process\Process;

class CheckSnInCRLCommandOutputTranslatorTest extends TestCase
{
    // Extrait de `openssl crl -text -noout` sur la CRL RGS_AC_PERSONNE_AUTHENTIFICATION_V1
    private const CRL_TEXT = <<<'OPENSSL_OUTPUT'
        Certificate Revocation List (CRL):
                Version 2 (0x1)
                Signature Algorithm: sha256WithRSAEncryption
                Issuer: C = FR, O = MINISTERE INTERIEUR, OU = 0002110014016, CN = AC PERSONNE AUTHENTIFICATION V1
                Last Update: Sep 23 12:39:06 2025 GMT
                Next Update: Sep 29 12:39:06 2025 GMT
                CRL extensions:
                    X509v3 Authority Key Identifier:
                        E3:F9:97:F0:BF:DE:E2:65:6D:BF:C2:5E:F6:3D:0E:D8:90:02:8B:90
                    2.5.29.60:
                        ..20210916134425Z
                    X509v3 CRL Number:
                        103807
        Revoked Certificates:
            Serial Number: 2CD4120F35BB
                Revocation Date: Dec  6 14:10:13 2021 GMT
            Serial Number: 2D5756A0413A
                Revocation Date: Nov 28 15:21:00 2023 GMT
            Serial Number: 7E7D0FBEC6BB898C
                Revocation Date: Jul 26 05:27:46 2022 GMT
            Serial Number: 7E7D2D8DFD990C6A
                Revocation Date: Jun 20 16:32:18 2022 GMT
            Serial Number: 7E7D345D95880549
                Revocation Date: Mar  9 15:55:12 2023 GMT
            Signature Algorithm: sha256WithRSAEncryption
            Signature Value:
                3c:be:0c:f7:cc:79:cb:0c:bf:b7:dd:a2:72:f4:4b:3d:ae:64:
                67:5b:d0:bd:6b:95:43:ba
        OPENSSL_OUTPUT;

    private function mockCRLProcess(): Process
    {
        $process = $this->createMock(Process::class);
        $process->method('getOutput')->willReturn(self::CRL_TEXT);
        return $process;
    }

    /**
     * @throws \S2lowLegacy\Class\RecoverableException
     */
    public function testRealCRLNoRevocation(): void
    {
        $translator = new CheckSnInCRLCommandOutputTranslator('7E7D2D8DFD990C6A', new DateTime('2022-06-20 16:32:17 GMT'));

        static::assertFalse(
            $translator->getCommandOutput($this->mockCRLProcess())->hasBlockingErrors()
        );
    }

    /**
     * @throws \S2lowLegacy\Class\RecoverableException
     */
    public function testRealCRLRevocation(): void
    {
        $translator = new CheckSnInCRLCommandOutputTranslator('7E7D2D8DFD990C6A', new DateTime('2022-06-20 16:32:19 GMT'));

        $analysedOutput = $translator->getCommandOutput($this->mockCRLProcess());
        static::assertTrue(
            $analysedOutput->hasBlockingErrors()
        );
        static::assertSame(
            'Certificat révoqué',
            $analysedOutput->getFirstBlockingErrorMessage()
        );
    }

    public function getLimitCases(): array
    {
        return [
            // 7E7D2D8DFD990C6A => Revocation Date: Jun 20 16:32:18 2022 GMT
            'signature à la seconde de la révocation' => ['7E7D2D8DFD990C6A', '2022-06-20 16:32:18 GMT', false],
            'fuseau horaire, avant révocation' => ['7E7D2D8DFD990C6A', '2022-06-20 18:32:17 Europe/Paris', false],
            'fuseau horaire, après révocation' => ['7E7D2D8DFD990C6A', '2022-06-20 18:32:19 Europe/Paris', true],
            // Le SN recherché est un préfixe de 7E7D2D8DFD990C6A mais n'est pas lui-même révoqué
            'SN préfixe d\'un SN révoqué' => ['7E7D2D8D', '2030-01-01 00:00:00 GMT', false],
            'SN absent de la CRL' => ['DEADBEEF00112233', '2030-01-01 00:00:00 GMT', false],
            // 2CD4120F35BB => Revocation Date: Dec  6 14:10:13 2021 GMT (jour sur un chiffre)
            'jour sur un chiffre, avant révocation' => ['2CD4120F35BB', '2021-12-06 14:10:12 GMT', false],
            'jour sur un chiffre, après révocation' => ['2CD4120F35BB', '2021-12-06 14:10:14 GMT', true],
        ];
    }

    /**
     * @dataProvider getLimitCases
     * @throws \S2lowLegacy\Class\RecoverableException
     */
    public function testRealCRLLimitCases(string $serialNumber, string $signatureDate, bool $isRevoked): void
    {
        $translator = new CheckSnInCRLCommandOutputTranslator($serialNumber, new DateTime($signatureDate));

        static::assertSame(
            $isRevoked,
            $translator->getCommandOutput($this->mockCRLProcess())->hasBlockingErrors()
        );
    }

    public function testBadDate(): void
    {
        $translator = new CheckSnInCRLCommandOutputTranslator('FD01', new DateTime('2022-06-20 16:32:19 GMT'));
        $process = $this->createMock(Process::class);
        $process->method('getOutput')->willReturn("Serial Number: FD01\nRevocation Date: Nope\n");

        self::expectException(RecoverableException::class);
        self::expectExceptionMessage('Failed to parse date: ');
        $translator->getCommandOutput($process);
    }
}
