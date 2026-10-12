<?php

declare(strict_types=1);

namespace IntegrationTests;

use S2low\Enum\UserRole;
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

    protected function setUp(): void
    {
        parent::setUp();
        $this->givenAnAuthorityAdministeredOnlyThroughModuleGroups(self::AUTHORITY_ADMINISTERED_FOR_HELIOS_ONLY, 0, self::GROUP_ID);
        $this->givenAnAuthorityAdministeredOnlyThroughModuleGroups(self::AUTHORITY_OF_ANOTHER_GROUP, self::OTHER_GROUP_ID, self::OTHER_GROUP_ID);
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
