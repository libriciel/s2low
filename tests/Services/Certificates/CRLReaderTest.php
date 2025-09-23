<?php

namespace S2low\Tests\Services\Certificates;

use DateTime;
use PHPUnit\Framework\TestCase;
use S2low\Services\Certificates\CRLReader;
use Symfony\Component\Process\Process;

class CRLReaderTest extends TestCase
{
    public function readCRL(): string
    {
        $process = new Process(['openssl', 'crl', '-in',__DIR__ . '/fixtures/RGS_AC_PERSONNE_AUTHENTIFICATION_V1_CRL.pem', '-text', '-noout']);
        $process->run();
        return $process->getOutput();
    }

    public function test()
    {
        $crl = (new CRLReader())->read($this->readCRL());
        $this->assertFalse(
            $crl->isRevoked('7E7D2D8DFD990C6A', new DateTime('2022-06-20 16:32:17 GMT'))
        );
        $this->assertTrue(
            $crl->isRevoked('7E7D2D8DFD990C6A', new DateTime('2022-06-20 16:32:19 GMT'))
        );
    }
}
