<?php

namespace PHPUnit\class;

use PHPUnit\Framework\TestCase;
use S2low\Exceptions\BadTypeTransactionCode;
use S2lowLegacy\Class\actes\TypeTransaction;

class TypeTransactionTest extends TestCase
{
    public function testGoodCode(): void
    {
        $this->expectNotToPerformAssertions();
        TypeTransaction::checkCode(1);
    }

    public function testBadCode(): void
    {
        $this->expectException(BadTypeTransactionCode::class);
        $this->expectExceptionMessage('Code 0 invalide, les valeurs possibles sont : ');
        TypeTransaction::checkCode(0);
    }
}
