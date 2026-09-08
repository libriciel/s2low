<?php

declare(strict_types=1);

namespace Test\PHPUnit\Services\Authority;

use S2low\DTO\ModuleActivationRequest;
use S2low\Enum\AdministeredModule;
use S2low\Exceptions\GroupDesignationRefusedException;
use S2low\Security\SecurityUser;
use S2low\Services\Authority\AdministeringGroupDesignation;
use S2lowLegacy\Lib\SQLQuery;
use S2lowLegacy\Model\UserSQL;
use S2lowTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class AdministeringGroupDesignationTest extends S2lowTestCase
{
    private const AUTHORITY = 1;
    private const CREATION = 0;

    private const GROUP = 1;
    private const OTHER_GROUP = 2;

    private const SUPER_ADMIN = 1;
    private const GROUP_ADMIN = 7;

    public function testTheSuperAdminDesignatesTheGroupsItChooses(): void
    {
        $this->connectAs(self::SUPER_ADMIN);

        $designated = $this->designation()->resolve(
            $this->request(self::AUTHORITY, [AdministeredModule::ACTES, AdministeredModule::HELIOS], [
                AdministeredModule::ACTES->value => self::GROUP,
                AdministeredModule::HELIOS->value => self::OTHER_GROUP,
            ])
        );

        static::assertSame(self::GROUP, $designated->groupIdFor(AdministeredModule::ACTES));
        static::assertSame(self::OTHER_GROUP, $designated->groupIdFor(AdministeredModule::HELIOS));
    }

    /**
     * Décocher un module ne le retire pas à son groupe : celui-ci reste administrateur et peut
     * le réactiver.
     */
    public function testADeactivatedModuleKeepsItsDesignation(): void
    {
        $this->givenAuthorityAdministeredBy(self::GROUP, self::OTHER_GROUP);
        $this->connectAs(self::SUPER_ADMIN);

        $designated = $this->designation()->resolve(
            $this->request(self::AUTHORITY, [], [])
        );

        static::assertSame(self::GROUP, $designated->groupIdFor(AdministeredModule::ACTES));
        static::assertSame(self::OTHER_GROUP, $designated->groupIdFor(AdministeredModule::HELIOS));
    }

    public function testAnActivatedModuleWithoutAChosenGroupIsRefused(): void
    {
        $this->connectAs(self::SUPER_ADMIN);

        $this->expectException(GroupDesignationRefusedException::class);
        $this->expectExceptionMessage('Le module Actes est activé');

        $this->designation()->resolve(
            $this->request(self::AUTHORITY, [AdministeredModule::ACTES], [])
        );
    }

    public function testAnInactiveGroupCannotBeDesignated(): void
    {
        $this->givenGroupIsDeactivated(self::OTHER_GROUP);
        $this->connectAs(self::SUPER_ADMIN);

        $this->expectException(GroupDesignationRefusedException::class);
        $this->expectExceptionMessage('est désactivé');

        $this->designation()->resolve(
            $this->request(self::AUTHORITY, [AdministeredModule::ACTES], [
                AdministeredModule::ACTES->value => self::OTHER_GROUP,
            ])
        );
    }

    /**
     * Un groupe désactivé après coup reste enregistrable tant qu'on ne le change pas, sinon la
     * collectivité ne serait plus modifiable sans lui changer de groupe.
     */
    public function testAnInactiveGroupAlreadyDesignatedRemainsSaveable(): void
    {
        $this->givenAuthorityAdministeredBy(self::OTHER_GROUP, self::OTHER_GROUP);
        $this->givenGroupIsDeactivated(self::OTHER_GROUP);
        $this->connectAs(self::SUPER_ADMIN);

        $designated = $this->designation()->resolve(
            $this->request(self::AUTHORITY, [AdministeredModule::ACTES], [
                AdministeredModule::ACTES->value => self::OTHER_GROUP,
            ])
        );

        static::assertSame(self::OTHER_GROUP, $designated->groupIdFor(AdministeredModule::ACTES));
    }

    /**
     * Un groupe n'administre une collectivité que si le SIREN qu'elle porte lui est réservé.
     */
    public function testAGroupThatDoesNotHoldTheSirenCannotBeDesignated(): void
    {
        $this->connectAs(self::SUPER_ADMIN);
        $this->givenSirenHeldBy(self::GROUP, '491011698');

        $this->expectException(GroupDesignationRefusedException::class);
        $this->expectExceptionMessage('ne détient pas le SIREN 491011698');

        $this->designation()->resolve(
            $this->request(self::AUTHORITY, [AdministeredModule::ACTES], [
                AdministeredModule::ACTES->value => self::OTHER_GROUP,
            ], '491011698')
        );
    }

    public function testAGroupHoldingTheSirenIsDesignated(): void
    {
        $this->connectAs(self::SUPER_ADMIN);
        $this->givenSirenHeldBy(self::OTHER_GROUP, '491011698');

        $designated = $this->designation()->resolve(
            $this->request(self::AUTHORITY, [AdministeredModule::ACTES], [
                AdministeredModule::ACTES->value => self::OTHER_GROUP,
            ], '491011698')
        );

        static::assertSame(self::OTHER_GROUP, $designated->groupIdFor(AdministeredModule::ACTES));
    }

    /**
     * Dépossédé du SIREN après coup, le groupe déjà désigné reste enregistrable : sinon la
     * collectivité ne pourrait plus être modifiée sans lui changer de groupe.
     */
    public function testTheGroupAlreadyDesignatedSurvivesLosingTheSiren(): void
    {
        $this->givenAuthorityAdministeredBy(self::OTHER_GROUP, self::OTHER_GROUP);
        $this->connectAs(self::SUPER_ADMIN);

        $designated = $this->designation()->resolve(
            $this->request(self::AUTHORITY, [AdministeredModule::ACTES], [
                AdministeredModule::ACTES->value => self::OTHER_GROUP,
            ], '491011698')
        );

        static::assertSame(self::OTHER_GROUP, $designated->groupIdFor(AdministeredModule::ACTES));
    }

    public function testAGroupAdminCreatingAnAuthorityDesignatesItsOwnGroupForTheActivatedModules(): void
    {
        $this->connectAs(self::GROUP_ADMIN);

        $designated = $this->designation()->resolve(
            $this->request(self::CREATION, [AdministeredModule::ACTES], [])
        );

        static::assertSame(self::GROUP, $designated->groupIdFor(AdministeredModule::ACTES));
        static::assertFalse($designated->isDesignatedFor(AdministeredModule::HELIOS));
    }

    /**
     * Le groupe ne se change pas hors création : celui que la collectivité a désigné reste le sien,
     * quoi que poste l'administrateur de groupe.
     */
    public function testAGroupAdminDoesNotTakeOverAModuleOfAnotherGroup(): void
    {
        $this->givenAuthorityAdministeredBy(self::OTHER_GROUP, self::OTHER_GROUP);
        $this->connectAs(self::GROUP_ADMIN);

        $designated = $this->designation()->resolve(
            $this->request(self::AUTHORITY, [AdministeredModule::ACTES], [
                AdministeredModule::ACTES->value => self::GROUP,
            ])
        );

        static::assertSame(self::OTHER_GROUP, $designated->groupIdFor(AdministeredModule::ACTES));
    }

    public function testAnAuthorityCreatedWithoutAnyModuleIsRefused(): void
    {
        $this->connectAs(self::SUPER_ADMIN);

        $this->expectException(GroupDesignationRefusedException::class);
        $this->expectExceptionMessage('au moins un groupe');

        $this->designation()->resolve($this->request(self::CREATION, [], []));
    }

    /**
     * Le SIREN reste vide par défaut : les cas qui ne portent pas sur lui échappent au contrôle de
     * détention, traité par ses propres tests.
     *
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

    private function designation(): AdministeringGroupDesignation
    {
        return self::getContainer()->get(AdministeringGroupDesignation::class);
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
