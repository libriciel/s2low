<?php

declare(strict_types=1);

namespace PHPUnit\class;

use S2lowLegacy\Class\AvailableSirensByGroup;
use S2lowTestCase;

class AvailableSirensByGroupTest extends S2lowTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->getSQLQuery()->query("INSERT INTO authority_groups VALUES (546, 'groupe avec id bizarre', 1)");
        $this->getSQLQuery()->query('INSERT INTO authority_group_siren VALUES (1,1,123456789)');
        $this->getSQLQuery()->query('INSERT INTO authority_group_siren VALUES (2,1,999999999)');
        $this->getSQLQuery()->query('INSERT INTO authority_group_siren VALUES (3,1,491011698)');
        $this->getSQLQuery()->query('INSERT INTO authority_group_siren VALUES (4,2,443783170)');
        $this->getSQLQuery()->query('INSERT INTO authority_group_siren VALUES (5,546,829864099)');
    }

    /**
     * @throws \Exception
     */
    public function tearDown(): void
    {
        $this->getSQLQuery()->query('DELETE FROM authority_group_siren');
        $this->getSQLQuery()->query('DELETE FROM authority_groups WHERE id=546');
        parent::tearDown();
    }
    public function testAvailableSirensByGroup()
    {
        /** @var AvailableSirensByGroup $availableSirensByGroup */
        $availableSirensByGroup = $this->getObjectInstancier()->get(AvailableSirensByGroup::class);
        self::assertSame(
            [
                [
                    546 => 'groupe avec id bizarre',
                    1 => 'Groupe de test',
                    2 => 'second groupe'
                ],
                [
                    546 => ['829864099'],
                    1 => ['491011698'],
                    2 => ['443783170']
                ]

            ],
            $availableSirensByGroup->get(6)
        );
    }
}
