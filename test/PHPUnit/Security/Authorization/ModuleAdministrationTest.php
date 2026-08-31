<?php

declare(strict_types=1);

namespace Test\PHPUnit\Security\Authorization;

use S2low\Security\Authorization\ModuleAdministration;
use S2low\Security\SecurityUser;
use S2lowLegacy\Lib\SQLQuery;
use S2lowLegacy\Model\UserSQL;
use S2lowTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class ModuleAdministrationTest extends S2lowTestCase
{
    private const AUTHORITY = 1;
    private const HELIOS_GROUP = 1;
    private const OTHER_GROUP = 2;

    private const SUPER_ADMIN = 1;
    private const HELIOS_GROUP_ADMIN = 7;
    private const OTHER_GROUP_ADMIN = 10;
    private const AUTHORITY_ADMIN = 3;
    private const SIMPLE_USER = 5;

    /**
     * @dataProvider usersFacingHelios
     */
    public function testOnlyTheSuperAdminAndTheHeliosGroupAdminAdministerHelios(int $userId, bool $expected): void
    {
        $this->designateHeliosGroup(self::HELIOS_GROUP);
        $this->connectAs($userId);

        static::assertSame($expected, $this->moduleAdministration()->isHeliosAdmin(self::AUTHORITY));
    }

    /**
     * @return array[]
     */
    public function usersFacingHelios(): array
    {
        return [
            'le super admin administre Helios' => [self::SUPER_ADMIN, true],
            'l_admin du groupe Helios administre Helios' => [self::HELIOS_GROUP_ADMIN, true],
            'l_admin d_un autre groupe n_administre pas Helios' => [self::OTHER_GROUP_ADMIN, false],
            'l_admin de collectivite n_administre pas Helios' => [self::AUTHORITY_ADMIN, false],
            'un utilisateur simple n_administre pas Helios' => [self::SIMPLE_USER, false],
        ];
    }

    public function testWithoutADesignatedGroupTheHeliosGroupAdminDoesNotAdminister(): void
    {
        $this->designateHeliosGroup(null);
        $this->connectAs(self::HELIOS_GROUP_ADMIN);

        static::assertFalse($this->moduleAdministration()->isHeliosAdmin(self::AUTHORITY));
    }

    public function testWithoutADesignatedGroupTheSuperAdminStillAdministers(): void
    {
        $this->designateHeliosGroup(null);
        $this->connectAs(self::SUPER_ADMIN);

        static::assertTrue($this->moduleAdministration()->isHeliosAdmin(self::AUTHORITY));
    }

    /**
     * La collectivité est en cours de création : c'est le groupe de son créateur qui sera désigné.
     */
    public function testOnAnAuthorityThatDoesNotExistYetAnyGroupAdminAdministers(): void
    {
        $this->connectAs(self::OTHER_GROUP_ADMIN);

        static::assertTrue($this->moduleAdministration()->isHeliosAdmin(0));
    }

    public function testOnAnAuthorityThatDoesNotExistYetTheAuthorityAdminDoesNotAdminister(): void
    {
        $this->connectAs(self::AUTHORITY_ADMIN);

        static::assertFalse($this->moduleAdministration()->isHeliosAdmin(0));
    }

    public function testOnAnAuthorityThatDoesNotExistYetTheSuperAdminStillAdministers(): void
    {
        $this->connectAs(self::SUPER_ADMIN);

        static::assertTrue($this->moduleAdministration()->isHeliosAdmin(0));
    }

    public function testNobodyConnectedAdministersNothing(): void
    {
        $this->designateHeliosGroup(self::HELIOS_GROUP);

        static::assertFalse($this->moduleAdministration()->isHeliosAdmin(self::AUTHORITY));
    }

    public function testTheActesGroupAdminAdministersActesAndNotHelios(): void
    {
        $this->designateActesGroup(self::OTHER_GROUP);
        $this->designateHeliosGroup(self::HELIOS_GROUP);
        $this->connectAs(self::OTHER_GROUP_ADMIN);

        static::assertTrue($this->moduleAdministration()->isActesAdmin(self::AUTHORITY));
        static::assertFalse($this->moduleAdministration()->isHeliosAdmin(self::AUTHORITY));
    }

    public function testTheHeliosGroupAdminAdministersHeliosAndNotActes(): void
    {
        $this->designateActesGroup(self::OTHER_GROUP);
        $this->designateHeliosGroup(self::HELIOS_GROUP);
        $this->connectAs(self::HELIOS_GROUP_ADMIN);

        static::assertTrue($this->moduleAdministration()->isHeliosAdmin(self::AUTHORITY));
        static::assertFalse($this->moduleAdministration()->isActesAdmin(self::AUTHORITY));
    }

    private function connectAs(int $userId): void
    {
        self::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken(
                new SecurityUser(self::getContainer()->get(UserSQL::class)->getUserById($userId)),
                'main'
            )
        );
    }

    private function moduleAdministration(): ModuleAdministration
    {
        return self::getContainer()->get(ModuleAdministration::class);
    }

    private function designateActesGroup(?int $groupId): void
    {
        self::getContainer()->get(SQLQuery::class)->query(
            'UPDATE authorities SET actes_group_id = ? WHERE id = ?',
            [$groupId, self::AUTHORITY]
        );
    }

    private function designateHeliosGroup(?int $groupId): void
    {
        self::getContainer()->get(SQLQuery::class)->query(
            'UPDATE authorities SET helios_group_id = ? WHERE id = ?',
            [$groupId, self::AUTHORITY]
        );
    }
}
