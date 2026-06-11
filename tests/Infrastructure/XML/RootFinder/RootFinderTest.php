<?php

namespace Infrastructure\XML\RootFinder;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use S2low\Infrastructure\XML\RootFinder\XMLRootFinder;
use SplFileObject;

class RootFinderTest extends TestCase
{
    public function testNoRoot(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No root element found');
        $rootFinder = new XMLRootFinder();
        $rootFinder->getRootElementName(new SplFileObject(__DIR__ . '/fixtures/textfile'));
    }
    public function test(): void
    {
        $rootFinder = new XMLRootFinder();
        self::assertSame(
            'root',
            $rootFinder->getRootElementName(new SplFileObject(__DIR__ . '/fixtures/test.xml'))
        );
    }
}
