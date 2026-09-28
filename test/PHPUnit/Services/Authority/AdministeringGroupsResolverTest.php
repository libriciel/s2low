<?php

declare(strict_types=1);

namespace Test\PHPUnit\Services\Authority;

use S2low\DTO\ModuleActivationRequest;
use S2low\Enum\AdministeredModule;
use S2low\Exceptions\GroupDesignationRefusedException;
use S2low\Security\SecurityUser;
use S2low\Services\Authority\AdministeringGroupsResolver;
use S2lowLegacy\Lib\SQLQuery;
use S2lowLegacy\Model\UserSQL;
use S2lowTestCase;

class AdministeringGroupsResolverTest extends S2lowTestCase
{
    private const int AUTHORITY = 1;
    private const int CREATION = 0;

    private const int GROUP = 1;
    private const int OTHER_GROUP = 2;

    private const string SIREN = '491011698';

    private const int SUPER_ADMIN = 1;
    private const int GROUP_ADMIN = 7;

    public function testTheSuperAdminDesignatesTheGroupsItChooses(): void
    {
        $designated = $this->resolver()->resolve(
            $this->request(self::AUTHORITY, [AdministeredModule::ACTES, AdministeredModule::HELIOS], [
                AdministeredModule::ACTES->value => self::GROUP,
                AdministeredModule::HELIOS->value => self::OTHER_GROUP,
            ]),
            $this->user(self::SUPER_ADMIN)
        );

        static::assertSame(self::GROUP, $designated->groupIdFor(AdministeredModule::ACTES));
        static::assertSame(self::OTHER_GROUP, $designated->groupIdFor(AdministeredModule::HELIOS));
    }

    public function testADeactivatedModuleKeepsItsDesignation(): void
    {
        $this->givenAuthorityAdministeredBy(self::GROUP, self::OTHER_GROUP);

        $designated = $this->resolver()->resolve(
            $this->request(self::AUTHORITY, [], []),
            $this->user(self::SUPER_ADMIN)
        );

        static::assertSame(self::GROUP, $designated->groupIdFor(AdministeredModule::ACTES));
        static::assertSame(self::OTHER_GROUP, $designated->groupIdFor(AdministeredModule::HELIOS));
    }

    public function testAnActivatedModuleWithoutAChosenGroupIsRefused(): void
    {
        $this->expectException(GroupDesignationRefusedException::class);
        $this->expectExceptionMessage('Le module Actes est activé');

        $this->resolver()->resolve(
            $this->request(self::AUTHORITY, [AdministeredModule::ACTES], []),
            $this->user(self::SUPER_ADMIN)
        );
    }

    public function testAnInactiveGroupCannotBeDesignated(): void
    {
        $this->givenGroupIsDeactivated(self::OTHER_GROUP);

        $this->expectException(GroupDesignationRefusedException::class);
        $this->expectExceptionMessage('est désactivé');

        $this->resolver()->resolve(
            $this->request(self::AUTHORITY, [AdministeredModule::ACTES], [
                AdministeredModule::ACTES->value => self::OTHER_GROUP,
            ]),
            $this->user(self::SUPER_ADMIN)
        );
    }

    public function testAnInactiveGroupAlreadyDesignatedRemainsSaveable(): void
    {
        $this->givenAuthorityAdministeredBy(self::OTHER_GROUP, self::OTHER_GROUP);
        $this->givenGroupIsDeactivated(self::OTHER_GROUP);

        $designated = $this->resolver()->resolve(
            $this->request(self::AUTHORITY, [AdministeredModule::ACTES], [
                AdministeredModule::ACTES->value => self::OTHER_GROUP,
            ]),
            $this->user(self::SUPER_ADMIN)
        );

        static::assertSame(self::OTHER_GROUP, $designated->groupIdFor(AdministeredModule::ACTES));
    }

    public function testAGroupThatDoesNotHoldTheSirenCannotBeDesignated(): void
    {
        $this->givenSirenHeldBy(self::GROUP, self::SIREN);

        $this->expectException(GroupDesignationRefusedException::class);
        $this->expectExceptionMessage('ne détient pas le SIREN ' . self::SIREN);

        $this->resolver()->resolve(
            $this->request(self::AUTHORITY, [AdministeredModule::ACTES], [
                AdministeredModule::ACTES->value => self::OTHER_GROUP,
            ], self::SIREN),
            $this->user(self::SUPER_ADMIN)
        );
    }

    public function testAGroupHoldingTheSirenIsDesignated(): void
    {
        $this->givenSirenHeldBy(self::OTHER_GROUP, self::SIREN);

        $designated = $this->resolver()->resolve(
            $this->request(self::AUTHORITY, [AdministeredModule::ACTES], [
                AdministeredModule::ACTES->value => self::OTHER_GROUP,
            ], self::SIREN),
            $this->user(self::SUPER_ADMIN)
        );

        static::assertSame(self::OTHER_GROUP, $designated->groupIdFor(AdministeredModule::ACTES));
    }

    public function testTheGroupAlreadyDesignatedSurvivesLosingTheSiren(): void
    {
        $this->givenAuthorityAdministeredBy(self::OTHER_GROUP, self::OTHER_GROUP);

        $designated = $this->resolver()->resolve(
            $this->request(self::AUTHORITY, [AdministeredModule::ACTES], [
                AdministeredModule::ACTES->value => self::OTHER_GROUP,
            ], self::SIREN),
            $this->user(self::SUPER_ADMIN)
        );

        static::assertSame(self::OTHER_GROUP, $designated->groupIdFor(AdministeredModule::ACTES));
    }

    public function testAGroupAdminCreatingAnAuthorityDesignatesItsOwnGroupForTheActivatedModules(): void
    {
        $designated = $this->resolver()->resolve(
            $this->request(self::CREATION, [AdministeredModule::ACTES], []),
            $this->user(self::GROUP_ADMIN)
        );

        static::assertSame(self::GROUP, $designated->groupIdFor(AdministeredModule::ACTES));
        static::assertFalse($designated->isDesignatedFor(AdministeredModule::HELIOS));
    }

    public function testAGroupAdminDoesNotTakeOverAModuleOfAnotherGroup(): void
    {
        $this->givenAuthorityAdministeredBy(self::OTHER_GROUP, self::OTHER_GROUP);

        $designated = $this->resolver()->resolve(
            $this->request(self::AUTHORITY, [AdministeredModule::ACTES], [
                AdministeredModule::ACTES->value => self::GROUP,
            ]),
            $this->user(self::GROUP_ADMIN)
        );

        static::assertSame(self::OTHER_GROUP, $designated->groupIdFor(AdministeredModule::ACTES));
    }

    public function testAnAuthorityCreatedWithoutAnyModuleIsRefused(): void
    {
        $this->expectException(GroupDesignationRefusedException::class);
        $this->expectExceptionMessage('au moins un groupe');

        $this->resolver()->resolve(
            $this->request(self::CREATION, [], []),
            $this->user(self::SUPER_ADMIN)
        );
    }

    /**
     * @param AdministeredModule[] $activatedModules
     * @param array<int, int> $chosenGroupIdByModule
     */
    private function request(
        int $authorityId,
        array $activatedModules,
        array $chosenGroupIdByModule,
        string $siren = ''
    ): ModuleActivationRequest {
        $activatedByModule = [];
        foreach (AdministeredModule::cases() as $module) {
            $activatedByModule[$module->value] = in_array($module, $activatedModules, true);
        }

        return new ModuleActivationRequest($authorityId, $siren, $activatedByModule, $chosenGroupIdByModule);
    }

    private function resolver(): AdministeringGroupsResolver
    {
        return self::getContainer()->get(AdministeringGroupsResolver::class);
    }

    private function user(int $userId): SecurityUser
    {
        return new SecurityUser(self::getContainer()->get(UserSQL::class)->getUserById($userId));
    }

    private function givenAuthorityAdministeredBy(int $actesGroupId, int $heliosGroupId): void
    {
        self::getContainer()->get(SQLQuery::class)->query(
            'UPDATE authorities SET actes_group_id = ?, helios_group_id = ? WHERE id = ?',
            [$actesGroupId, $heliosGroupId, self::AUTHORITY]
        );
    }

    private function givenSirenHeldBy(int $groupId, string $siren): void
    {
        self::getContainer()->get(SQLQuery::class)->query(
            'INSERT INTO authority_group_siren (authority_group_id, siren) VALUES (?, ?)',
            [$groupId, $siren]
        );
    }

    private function givenGroupIsDeactivated(int $groupId): void
    {
        self::getContainer()->get(SQLQuery::class)->query(
            'UPDATE authority_groups SET status = 0 WHERE id = ?',
            [$groupId]
        );
    }
}
