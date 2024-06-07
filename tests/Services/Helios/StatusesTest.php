<?php

declare(strict_types=1);

namespace S2low\Tests\Services\Helios;

use Exception;
use RuntimeException;
use S2low\Services\Helios\Statuses;
use S2lowLegacy\Class\helios\HeliosStatusSQL;
use S2lowTestCase;

class StatusesTest extends S2lowTestCase
{
    /**
     * @throws Exception
     */
    public function testgetNameById(): void
    {
        /** @var HeliosStatusSQL $statusSQL */
        $statusSQL = $this->getObjectInstancier()->get(HeliosStatusSQL::class);
        $statuses = new Statuses($statusSQL->getAllStatus());
        self::assertSame('Posté', $statuses->getNameById(1));
    }

    public function testStrangeIndex(): void
    {
        $statuses = new Statuses([1 => ['id' => 2,'name' => 'Mauvaise pioche'],454 => ['id' => 1,'name' => 'Posté']]);
        self::assertSame('Posté', $statuses->getNameById(1));
    }

    public function testGetNonExistingStatus(): void
    {
        $statuses = new Statuses([]);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Status 1 not found');
        self::assertSame('Posté', $statuses->getNameById(1));
    }

    public function testGetStatusDoubles(): void
    {
        $statuses = new Statuses([['id' => 1,'name' => 'foo'],['id' => 1,'name' => 'bar']]);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Statuts non cohérents');
        self::assertSame('Posté', $statuses->getNameById(1));
    }

    public function testGetAllStatuses(): void
    {
        $statusesArray = [['id' => 1,'name' => 'foo'],['id' => 1,'name' => 'bar']];
        $statuses = new Statuses($statusesArray);
        self::assertSame(
            $statusesArray,
            $statuses->getStatuses()
        );
    }
}
