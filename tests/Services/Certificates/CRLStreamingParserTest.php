<?php

declare(strict_types=1);

namespace S2low\Tests\Services\Certificates;

use DateTime;
use PHPUnit\Framework\TestCase;
use S2low\Exceptions\CrlParsingException;
use S2low\Services\Certificates\CRLStreamingParser;
use Symfony\Component\Process\Process;

class CRLStreamingParserTest extends TestCase
{
    public function readCRL(): string
    {
        $process = new Process(['openssl', 'crl', '-in',__DIR__ . '/fixtures/RGS_AC_PERSONNE_AUTHENTIFICATION_V1_CRL.pem', '-text', '-noout']);
        $process->run();
        return $process->getOutput();
    }

    /**
     * @throws \S2low\Exceptions\CrlParsingException
     */
    public function testRealCRLNoRevocation(): void
    {
        $streamingParser = new CRLStreamingParser('7E7D2D8DFD990C6A', new DateTime('2022-06-20 16:32:17 GMT'));

        $lines = preg_split('/\R/', $this->readCRL());
        foreach ($lines as $line) {
            $streamingParser->parseLine($line);
        }
        static::assertTrue(
            $streamingParser->isFinished()
        );
        static::assertFalse(
            $streamingParser->getResult()
        );
    }

    /**
     * @throws \S2low\Exceptions\CrlParsingException
     */
    public function testRealCRLRevocation(): void
    {
        $streamingParser = new CRLStreamingParser('7E7D2D8DFD990C6A', new DateTime('2022-06-20 16:32:19 GMT'));

        $lines = preg_split('/\R/', $this->readCRL());
        foreach ($lines as $line) {
            $streamingParser->parseLine($line);
        }
        static::assertTrue(
            $streamingParser->isFinished()
        );
        static::assertTrue(
            $streamingParser->getResult()
        );
    }

    public function testBadDate(): void
    {
        $streamingParser = new CRLStreamingParser('FD01', new DateTime('2022-06-20 16:32:19 GMT'));

        self::expectException(CrlParsingException::class);
        self::expectExceptionMessage('Failed to parse date: ');
        $streamingParser->parseLine('Serial Number: FD01');
        $streamingParser->parseLine('Revocation Date: Nope');
    }
}
