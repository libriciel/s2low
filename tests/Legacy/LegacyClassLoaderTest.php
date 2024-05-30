<?php

declare(strict_types=1);

namespace S2low\Tests\Legacy;

use PHPUnit\Framework\TestCase;
use S2low\Legacy\LegacyClassLoader;

class LegacyClassLoaderTest extends TestCase
{
    /**
     * @dataProvider files
     */
    public function testLegacyClassLoader(string $file, string $expectedClass)
    {
        $legacyClassLoader = new LegacyClassLoader();
        static::assertEquals(
            $expectedClass,
            $legacyClassLoader->getClassName($file)
        );
    }
    public function files(): iterable
    {
        yield [__DIR__ . '/fixtures/TestClass.php', 'S2low\Tests\Legacy\fixtures\TestClass'];
        yield [__DIR__ . '/fixtures/TestNotAClass.php', ''];
        yield [__DIR__ . '/fixtures/TestBadClass.php', ''];
        yield [__DIR__ . '/fixtures/FileWithDie.php', ''];
    }
}
