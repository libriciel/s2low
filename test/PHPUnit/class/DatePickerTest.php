<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use S2lowLegacy\Class\DatePicker;

class DatePickerTest extends TestCase
{
    public static function dateProvider(): \Generator
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
}
