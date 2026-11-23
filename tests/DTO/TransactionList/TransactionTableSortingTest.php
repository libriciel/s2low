<?php

declare(strict_types=1);

namespace S2low\Tests\DTO\TransactionList;

use PHPUnit\Framework\TestCase;
use S2low\DTO\TransactionList\TransactionTableSorting;
use S2low\Enum\SortDirection;

class TransactionTableSortingTest extends TestCase
{
    private const string PAGE_URL = 'https://s2low.test/modules/actes/index.php';

    public function testWithoutOrderTheListIsSortedByIdDescending(): void
    {
        $sorting = TransactionTableSorting::fromQuery(self::PAGE_URL, []);

        static::assertSame(SortDirection::Desc, $sorting->byId->activeDirection);
        static::assertNull($sorting->bySubmissionDate->activeDirection);
    }

    public function testTheActiveColumnLinkReversesTheDirection(): void
    {
        $sorting = TransactionTableSorting::fromQuery(self::PAGE_URL, ['order' => 'id', 'sortway' => 'desc']);

        static::assertSame(self::PAGE_URL . '?order=id&sortway=asc', $sorting->byId->url);
    }

    public function testTheInactiveColumnLinkStartsDescending(): void
    {
        $sorting = TransactionTableSorting::fromQuery(self::PAGE_URL, ['order' => 'id', 'sortway' => 'asc']);

        static::assertSame(self::PAGE_URL . '?order=submission_date&sortway=desc', $sorting->bySubmissionDate->url);
    }

    public function testAnyOtherOrderMeansSubmissionDateLikeTheSqlQuery(): void
    {
        $sorting = TransactionTableSorting::fromQuery(self::PAGE_URL, ['order' => 'whatever', 'sortway' => 'asc']);

        static::assertSame(SortDirection::Asc, $sorting->bySubmissionDate->activeDirection);
        static::assertNull($sorting->byId->activeDirection);
    }

    public function testFiltersAreKeptInTheSortLinks(): void
    {
        $sorting = TransactionTableSorting::fromQuery(
            self::PAGE_URL,
            ['type' => '1', 'objet' => 'école & mairie', 'order' => 'id']
        );

        static::assertSame(
            self::PAGE_URL . '?type=1&objet=%C3%A9cole+%26+mairie&order=submission_date&sortway=desc',
            $sorting->bySubmissionDate->url
        );
    }
}
