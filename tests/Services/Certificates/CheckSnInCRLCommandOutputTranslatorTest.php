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
    public function readCRL(): Process
    {
        $process = new Process(['openssl', 'crl', '-in',__DIR__ . '/fixtures/RGS_AC_PERSONNE_AUTHENTIFICATION_V1_CRL.pem', '-text', '-noout']);
        $process->run();
        return $process;
    }

    /**
     * @throws \S2lowLegacy\Class\RecoverableException
     */
    public function testRealCRLNoRevocation(): void
    {
        $translator = new CheckSnInCRLCommandOutputTranslator('7E7D2D8DFD990C6A', new DateTime('2022-06-20 16:32:17 GMT'));

        static::assertFalse(
            $translator->getCommandOutput($this->readCRL())->hasBlockingErrors()
        );
    }

    /**
     * @throws \S2lowLegacy\Class\RecoverableException
     */
    public function testRealCRLRevocation(): void
    {
        $translator = new CheckSnInCRLCommandOutputTranslator('7E7D2D8DFD990C6A', new DateTime('2022-06-20 16:32:19 GMT'));

        $analysedOutput = $translator->getCommandOutput($this->readCRL());
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
            $translator->getCommandOutput($this->readCRL())->hasBlockingErrors()
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
