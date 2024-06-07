<?php

namespace PHPUnit\class\helios;

use Generator;
use S2lowLegacy\Class\helios\HeliosStatusSQL;
use S2lowTestCase;

class HeliosStatusSQLTest extends S2lowTestCase
{
    public function testGetAllStatus(): void
    {
        $statusSQL = $this->getObjectInstancier()->get(HeliosStatusSQL::class);
        static::assertSame(
            [['id' => 1,'name' => 'Posté']],
            $statusSQL->getAllStatus()
        );
    }

    /**
     * @dataProvider status
     */
    public function testGetStatusLibelle(int $status, string|int $libelle): void
    {
        static::assertSame($libelle, HeliosStatusSQL::getStatusLibelle($status));
    }

    public function status(): Generator
    {
        yield [19,'En attente de transmission au SAE'];
        yield [1,1];
    }
}
