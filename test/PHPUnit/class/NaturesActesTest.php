<?php

namespace PHPUnit\class;

use PHPUnit\Framework\TestCase;
use S2low\Exceptions\BadNatureCodeException;
use S2lowLegacy\Class\actes\NaturesActes;

class NaturesActesTest extends TestCase
{
    public function testNaturesActes(): void
    {
        self::assertSame(
            NaturesActes::getFromString('99_DE'),
            NaturesActes::DE
        );
    }

    public function testWrongNaturesActes(): void
    {
        self::expectException(BadNatureCodeException::class);
        self::expectExceptionMessage(
            'Code invalide : valeur parmi 99_DE, 99_AR, 99_AI, 99_CC, 99_BF, 99_AU attendue, 99_OUPS fourni'
        );
        NaturesActes::getFromString('99_OUPS');
    }
}
