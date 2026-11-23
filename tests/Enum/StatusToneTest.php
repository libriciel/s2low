<?php

declare(strict_types=1);

namespace S2low\Tests\Enum;

use PHPUnit\Framework\TestCase;
use S2low\Enum\StatusTone;

class StatusToneTest extends TestCase
{
    /**
     * @dataProvider symbolProvider
     */
    public function testEachToneHasItsOwnSymbol(StatusTone $tone, string $expectedSymbol): void
    {
        static::assertSame($expectedSymbol, $tone->symbol());
    }

    public static function symbolProvider(): iterable
    {
        yield [StatusTone::Done, '✓'];
        yield [StatusTone::InProgress, '→'];
        yield [StatusTone::Waiting, '…'];
        yield [StatusTone::Failed, '!'];
        yield [StatusTone::Inactive, '–'];
    }

    public function testSymbolsAreAllDifferent(): void
    {
        $symbols = array_map(static fn (StatusTone $tone): string => $tone->symbol(), StatusTone::cases());

        static::assertSame($symbols, array_unique($symbols));
    }
}
