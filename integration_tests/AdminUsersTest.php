<?php

declare(strict_types=1);

namespace IntegrationTests;

use S2low\Enum\UserRole;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Lib\SQLQuery;
use Symfony\Component\DomCrawler\Crawler;

class AdminUsersTest extends S2lowIntegrationTestCase
{
    private const int GROUP_ID = 1;
    private const int OTHER_GROUP_ID = 2;
    private const int AUTHORITY_ADMINISTERED_FOR_HELIOS_ONLY = 2;
    private const string NAME_OF_AUTHORITY_ADMINISTERED_FOR_HELIOS_ONLY = 'Saint-Andre de Corcy';
    private const int AUTHORITY_OF_ANOTHER_GROUP = 3;
    private const int USER_OF_AUTHORITY_ADMINISTERED_FOR_HELIOS_ONLY = 3;
    private const int AUTHORITY_ID = 1;
    private const int USER_OF_THE_AUTHORITY = 8;

    protected function setUp(): void
    {
        parent::setUp();
        $this->givenAnAuthorityAdministeredOnlyThroughModuleGroups(self::AUTHORITY_ADMINISTERED_FOR_HELIOS_ONLY, 0, self::GROUP_ID);
        $this->givenAnAuthorityAdministeredOnlyThroughModuleGroups(self::AUTHORITY_OF_ANOTHER_GROUP, self::OTHER_GROUP_ID, self::OTHER_GROUP_ID);
    }

    public function testAGroupAdminFiltersUsersOnAnAuthorityAdministeredForASingleModule(): void
    {
        $this->setUserWithRole(UserRole::AdministrateurGroupe);

        $_GET = [];
        $page = $this->client->request('GET', '/admin/users/admin_users.php');

        static::assertTrue($this->offersAuthority($page, 'authority', self::AUTHORITY_ADMINISTERED_FOR_HELIOS_ONLY));
        static::assertFalse($this->offersAuthority($page, 'authority', self::AUTHORITY_OF_ANOTHER_GROUP));
    }

    public function testAGroupAdminEditsAUserOfAnAuthorityAdministeredForASingleModule(): void
    {
        $this->setUserWithRole(UserRole::AdministrateurGroupe);

        $_GET = ['id' => (string)self::USER_OF_AUTHORITY_ADMINISTERED_FOR_HELIOS_ONLY];
        $page = $this->client->request('GET', '/admin/users/admin_user_edit.php');

        $authorityName = self::NAME_OF_AUTHORITY_ADMINISTERED_FOR_HELIOS_ONLY;
        static::assertCount(
            1,
            $page->filterXPath("//label[contains(., 'Collectivité')]/following-sibling::div[contains(., '$authorityName')]")
        );
    }

    public function testAGroupAdminAttachesAUserToAnAuthorityAdministeredForASingleModule(): void
    {
        $this->setUserWithRole(UserRole::AdministrateurGroupe);

        $_GET = [];
        $page = $this->client->request('GET', '/admin/users/admin_user_edit.php');

        static::assertTrue($this->offersAuthority($page, 'authority_id', self::AUTHORITY_ADMINISTERED_FOR_HELIOS_ONLY));
        static::assertFalse($this->offersAuthority($page, 'authority_id', self::AUTHORITY_OF_ANOTHER_GROUP));
    }

    public function testASuperAdminFilteringOnAGroupSeesUsersOfAnAuthorityAdministeredForASingleModule(): void
    {
        $this->setUserWithRole(UserRole::SuperAdministrateur);

        $_GET = ['status' => '', 'group' => (string)self::GROUP_ID];
        $page = $this->client->request('GET', '/admin/users/admin_users.php');

        $userId = self::USER_OF_AUTHORITY_ADMINISTERED_FOR_HELIOS_ONLY;
        static::assertCount(1, $page->filterXPath("//a[contains(@href, 'admin_user_edit.php?id=$userId')]"));
    }

    public function testTheActesGroupAdminSeesOnlyTheActesPermissionOfAUser(): void
    {
        $this->givenAnAuthorityAdministeredOnlyThroughModuleGroups(self::AUTHORITY_ID, self::GROUP_ID, self::OTHER_GROUP_ID);
        $this->setUserWithRole(UserRole::AdministrateurGroupe);

        $page = $this->whenTheUserIsEdited();

        static::assertTrue($this->offersPermission($page, Module::ACTES));
        static::assertFalse($this->offersPermission($page, Module::HELIOS));
    }

    public function testTheHeliosGroupAdminSeesOnlyTheHeliosPermissionOfAUser(): void
    {
        $this->givenAnAuthorityAdministeredOnlyThroughModuleGroups(self::AUTHORITY_ID, self::OTHER_GROUP_ID, self::GROUP_ID);
        $this->setUserWithRole(UserRole::AdministrateurGroupe);

        $page = $this->whenTheUserIsEdited();

        static::assertFalse($this->offersPermission($page, Module::ACTES));
        static::assertTrue($this->offersPermission($page, Module::HELIOS));
    }

    public function testTheAdminOfBothGroupsSeesBothPermissions(): void
    {
        $this->givenAnAuthorityAdministeredOnlyThroughModuleGroups(self::AUTHORITY_ID, self::GROUP_ID, self::GROUP_ID);
        $this->setUserWithRole(UserRole::AdministrateurGroupe);

        $page = $this->whenTheUserIsEdited();

        static::assertTrue($this->offersPermission($page, Module::ACTES));
        static::assertTrue($this->offersPermission($page, Module::HELIOS));
    }

    public function testTheMailPermissionStaysVisibleToTheAdminOfASingleModule(): void
    {
        $this->givenAnAuthorityAdministeredOnlyThroughModuleGroups(self::AUTHORITY_ID, self::GROUP_ID, self::OTHER_GROUP_ID);
        $this->givenTheMailModuleActivatedForTheAuthority();
        $this->setUserWithRole(UserRole::AdministrateurGroupe);

        $page = $this->whenTheUserIsEdited();

        static::assertTrue($this->offersPermission($page, Module::MAIL));
    }

    public function testTheSuperAdminSeesEveryPermission(): void
    {
        $this->givenAnAuthorityAdministeredOnlyThroughModuleGroups(self::AUTHORITY_ID, self::OTHER_GROUP_ID, self::OTHER_GROUP_ID);
        $this->setUserWithRole(UserRole::SuperAdministrateur);

        $page = $this->whenTheUserIsEdited();

        static::assertTrue($this->offersPermission($page, Module::ACTES));
        static::assertTrue($this->offersPermission($page, Module::HELIOS));
        static::assertTrue($this->offersPermission($page, Module::MAIL));
    }

    public function testEachAuthorityOfferedAtCreationListsTheModulesTheGroupAdministers(): void
    {
        $this->givenAnAuthorityAdministeredOnlyThroughModuleGroups(self::AUTHORITY_ID, self::GROUP_ID, self::GROUP_ID);
        $this->setUserWithRole(UserRole::AdministrateurGroupe);

        $page = $this->whenAUserIsCreated();

        static::assertSame('1,2,3', $this->permissionModulesOffered($page, self::AUTHORITY_ID));
        static::assertSame('2,3', $this->permissionModulesOffered($page, self::AUTHORITY_ADMINISTERED_FOR_HELIOS_ONLY));
    }

    public function testTheCreationFormOffersThePermissionsOfEveryModuleTheGroupAdministersSomewhere(): void
    {
        $this->givenAnAuthorityAdministeredOnlyThroughModuleGroups(self::AUTHORITY_ID, self::GROUP_ID, self::OTHER_GROUP_ID);
        $this->setUserWithRole(UserRole::AdministrateurGroupe);

        $page = $this->whenAUserIsCreated();

        static::assertTrue($this->offersPermission($page, Module::ACTES));
        static::assertTrue($this->offersPermission($page, Module::HELIOS));
    }

    private function whenTheUserIsEdited(): Crawler
    {
        $_GET = ['id' => (string)self::USER_OF_THE_AUTHORITY];

        return $this->client->request('GET', '/admin/users/admin_user_edit.php?id=' . self::USER_OF_THE_AUTHORITY);
    }

    private function whenAUserIsCreated(): Crawler
    {
        $_GET = [];

        return $this->client->request('GET', '/admin/users/admin_user_edit.php');
    }

    private function givenTheMailModuleActivatedForTheAuthority(): void
    {
        self::getContainer()->get(SQLQuery::class)->query(
            'INSERT INTO modules_authorities (module_id, authority_id) VALUES (?, ?)',
            [Module::MAIL, self::AUTHORITY_ID]
        );
    }

    private function offersPermission(Crawler $page, int $moduleId): bool
    {
        return $page->filterXPath("//select[@name='perm_$moduleId']")->count() === 1;
    }

    private function permissionModulesOffered(Crawler $page, int $authorityId): string
    {
        return (string)$page
            ->filterXPath("//select[@name='authority_id']/option[@value='$authorityId']")
            ->attr('data-permission-modules');
    }

    private function givenAnAuthorityAdministeredOnlyThroughModuleGroups(int $authorityId, int $actesGroupId, int $heliosGroupId): void
    {
        self::getContainer()->get(SQLQuery::class)->query(
            'UPDATE authorities SET authority_group_id = NULL, actes_group_id = NULLIF(?, 0), helios_group_id = NULLIF(?, 0) WHERE id = ?',
            [$actesGroupId, $heliosGroupId, $authorityId]
        );
    }

    private function offersAuthority(Crawler $page, string $selectName, int $authorityId): bool
    {
        return $page->filterXPath("//select[@name='$selectName']/option[@value='$authorityId']")->count() === 1;
    }
}
