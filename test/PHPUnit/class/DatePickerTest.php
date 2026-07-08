<?php

declare(strict_types=1);

namespace PHPUnit\class;

use Generator;
use JsonException;
use S2lowLegacy\Class\DatePicker;
use S2lowTestCase;

class DatePickerTest extends S2lowTestCase
{
    public static function dateProvider(): Generator
    {
        yield ['2000-01-01', '01 janvier 2000'];
        yield ['2023-12-31', '31 décembre 2023'];
        yield ['2024-13-40', '09 février 2025'];
    }

    /**
     * @dataProvider dateProvider
     */
    public function testGetFormattedDate(string $date, string $expected): void
    {
        static::assertSame(
            $expected,
            (new DatePicker('test'))->getFormattedDate($date, DatePicker::FORMAT)
        );
    }

    /**
     * @throws JsonException
     */
    public function testNullAndEmptyAreSame(): void
    {
        static::assertSame(
            (new DatePicker('test', null))->show(),
            (new DatePicker('test', ''))->show()
        );
    }
}
