<?php

declare(strict_types=1);

namespace PHPUnit\lib;

use Exception;
use PHPUnit\S2lowTestCase;
use S2lowLegacy\Lib\PemCertificateFactory;

class PemCertificateFactoryTest extends S2lowTestCase
{
    public function testExceptionIsThrownWhenWrongCertificateIsParsed()
    {
        $certificateFactory = new PemCertificateFactory();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Problème à l'ouverture du certificat : ");
        $certificateFactory->getFromString("Pas un certificat");
    }
}
