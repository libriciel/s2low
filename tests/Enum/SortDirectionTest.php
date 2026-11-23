<?php

declare(strict_types=1);

namespace S2low\Tests\Enum;

use PHPUnit\Framework\TestCase;
use S2low\Enum\SortDirection;

class SortDirectionTest extends TestCase
{
    public function testAnAscendingSortIsAnnouncedAsAscending(): void
    {
        static::assertSame('ascending', SortDirection::Asc->ariaSort());
    }

    public function testADescendingSortIsAnnouncedAsDescending(): void
    {
        static::assertSame('descending', SortDirection::Desc->ariaSort());
    }
}
