<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use S2lowLegacy\Lib\ObjectInstancier;

class ObjectInstancierTest extends TestCase
{
    public function testRecupValue(): void
    {
        $objectInstancier = new ObjectInstancier();
        $objectInstancier->foo = 'bar';
        static::assertSame('bar', $objectInstancier->foo);
    }

    public function testMakeObject(): void
    {
        $objectInstancier = new ObjectInstancier();
        $mockClass = $objectInstancier->MockClass;
        static::assertInstanceOf('MockClass', $mockClass);
    }


    public function testMakeObjectParamFail(): void
    {
        $objectInstancier = new ObjectInstancier();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Impossible d'instancier MockClassParam car le parametre param est manquant");
        $objectInstancier->MockClassParam;
    }

    public function testMakeObjectParam(): void
    {
        $objectInstancier = new ObjectInstancier();
        $objectInstancier->param = 42;
        $mockClass = $objectInstancier->MockClassParam;
        static::assertInstanceOf('MockClassParam', $mockClass);
    }

    public function testMakeObjectParamOptionnal(): void
    {
        $objectInstancier = new ObjectInstancier();
        $mockClass = $objectInstancier->MockClassParamOptional;
        static::assertInstanceOf('MockClassParamOptional', $mockClass);
    }

    public function testGetObjectWithPrimitiveTypeHint(): void
    {
        $objectInstancier = new ObjectInstancier();
        $objectInstancier->set('foo', 100);
        $class = new class (1) {
            public function __construct(public int $foo)
            {
            }
        };

        self::assertSame(100, $objectInstancier->get($class::class)->foo);
    }
}
