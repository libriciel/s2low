<?php

declare(strict_types=1);

class AuthorityGroupDesignationTest extends S2lowTestCase
{
    private const int AUTHORITY_ID = 2;

    private int $groupId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->groupId = (int)$this->getSQLQuery()->queryOne(
            "INSERT INTO authority_groups (name, status) VALUES ('Groupe sans utilisateur', 1) RETURNING id"
        );
    }

    public static function groupColumns(): array
    {
        return [
            'Actes' => ['actes_group_id', 'authorities_actes_group_id_fk'],
            'Helios' => ['helios_group_id', 'authorities_helios_group_id_fk'],
        ];
    }

    /**
     * @dataProvider groupColumns
     */
    public function testDatabaseRefusesToDeleteAGroupStillDesignated(string $groupColumn, string $constraint): void
    {
        $this->getSQLQuery()->query(
            "UPDATE authorities SET $groupColumn = ? WHERE id = ?",
            [$this->groupId, self::AUTHORITY_ID]
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage($constraint);

        $this->getSQLQuery()->query('DELETE FROM authority_groups WHERE id = ?', [$this->groupId]);
    }

    public function testDatabaseAllowsDeletingAGroupNoLongerDesignated(): void
    {
        $this->getSQLQuery()->query('DELETE FROM authority_groups WHERE id = ?', [$this->groupId]);

        static::assertSame(
            [],
            $this->getSQLQuery()->query('SELECT id FROM authority_groups WHERE id = ?', [$this->groupId])
        );
    }
}
