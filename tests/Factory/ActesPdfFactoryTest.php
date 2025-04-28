<?php

namespace S2low\Tests\Factory;

use S2low\Factory\ActesPdfFactory;
use PHPUnit\Framework\TestCase;
use S2lowLegacy\Class\actes\ActesPdf;
use S2lowLegacy\Class\actes\ActesPdfLegacy;

class ActesPdfFactoryTest extends TestCase
{
    private const CREATE_LEGACY_ACTE_PDF = true;
    private const DONT_CREATE_LEGACY_ACTE_PDF = false;

    public function testCreateLegacyActePdf()
    {
        $factory = new ActesPdfFactory(
            self::CREATE_LEGACY_ACTE_PDF
        );
        $createdActesPdf = $factory->create();

        $this->assertInstanceOf(ActesPdfLegacy::class, $createdActesPdf);
    }

    public function testCreateActePdf()
    {
        $factory = new ActesPdfFactory(
            self::DONT_CREATE_LEGACY_ACTE_PDF
        );
        $createdActesPdf = $factory->create();

        $this->assertInstanceOf(ActesPdf::class, $createdActesPdf);
    }
}
