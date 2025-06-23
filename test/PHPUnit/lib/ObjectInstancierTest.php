<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use S2lowLegacy\Lib\ObjectInstancier;

class ObjectInstancierTest extends S2lowTestCase
{
    public function testRecupValue(): void
    {
        $objectInstancier = new ObjectInstancier(self::getContainer());
        $objectInstancier->foo = 'bar';
        static::assertSame('bar', $objectInstancier->foo);
    }
}
