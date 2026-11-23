<?php

declare(strict_types=1);

namespace S2low\Tests\Enum;

use PHPUnit\Framework\TestCase;
use S2low\Enum\StatusTone;

class StatusToneTest extends TestCase
{
    /**
     * @dataProvider iconPathProvider
     */
    public function testEachToneHasItsOwnIcon(StatusTone $tone, string $expectedIconPath): void
    {
        static::assertSame($expectedIconPath, $tone->iconPath());
    }

    public static function iconPathProvider(): iterable
    {
        yield [StatusTone::Done, 'M5 8.2l2 2 4-4.2'];
        yield [StatusTone::InProgress, 'M5 8h6M8.5 5.5 11 8l-2.5 2.5'];
        yield [StatusTone::Waiting, 'M8 4.5V8l2.3 1.5'];
        yield [StatusTone::Failed, 'M8 4.5v4M8 11.2v.01'];
        yield [StatusTone::Inactive, 'M5.2 8h5.6'];
    }

    public function testIconsAreAllDifferent(): void
    {
        $iconPaths = array_map(static fn (StatusTone $tone): string => $tone->iconPath(), StatusTone::cases());

        static::assertSame($iconPaths, array_unique($iconPaths));
    }
}
