<?php

declare(strict_types=1);

namespace IntegrationTests;

use S2low\Enum\UserRole;
use S2lowLegacy\Lib\SQLQuery;

class AvailableSirensControllerTest extends S2lowIntegrationTestCase
{
    private const GROUP_1 = 1;
    private const GROUP_2 = 2;
    private const SIREN_OF_BOTH_GROUPS = '491011698';
    private const SIREN_OF_GROUP_1 = '111111119';

    /**
     * @throws \Exception
     */
    public function testTheSuperAdminGetsWhatBothGroupsAllow(): void
    {
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $this->givenSirenAuthorizedForGroup(self::GROUP_1, self::SIREN_OF_BOTH_GROUPS);
        $this->givenSirenAuthorizedForGroup(self::GROUP_2, self::SIREN_OF_BOTH_GROUPS);
        $this->givenSirenAuthorizedForGroup(self::GROUP_1, self::SIREN_OF_GROUP_1);

        $sirens = $this->whenTheAvailableSirensAreAsked('actes_group_id=1&helios_group_id=2');

        static::assertSame([self::SIREN_OF_BOTH_GROUPS], $sirens);
    }

    /**
     * @throws \Exception
     */
    public function testASingleGroupGivesItsOwnSirens(): void
    {
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $this->givenSirenAuthorizedForGroup(self::GROUP_1, self::SIREN_OF_BOTH_GROUPS);
        $this->givenSirenAuthorizedForGroup(self::GROUP_1, self::SIREN_OF_GROUP_1);

        $sirens = $this->whenTheAvailableSirensAreAsked('actes_group_id=1');

        static::assertSame([self::SIREN_OF_GROUP_1, self::SIREN_OF_BOTH_GROUPS], $sirens);
    }

    /**
     * @throws \Exception
     */
    public function testAGroupAdminIsDenied(): void
    {
        $this->setUserWithRole(UserRole::AdministrateurGroupe);
        $this->givenSirenAuthorizedForGroup(self::GROUP_1, self::SIREN_OF_BOTH_GROUPS);

        $this->client->request('GET', '/api/authorities/available-sirens?actes_group_id=1');

        static::assertTrue($this->client->getResponse()->isRedirection());
        static::assertStringNotContainsString(self::SIREN_OF_BOTH_GROUPS, $this->client->getResponse()->getContent());
    }

    /**
     * @throws \Exception
     */
    private function whenTheAvailableSirensAreAsked(string $queryString): array
    {
        $this->client->request('GET', "/api/authorities/available-sirens?$queryString&authority_id=0");

        static::assertSame(200, $this->client->getResponse()->getStatusCode());

        return json_decode(
            $this->client->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        )['sirens'];
    }

    private function givenSirenAuthorizedForGroup(int $groupId, string $siren): void
    {
        self::getContainer()->get(SQLQuery::class)->query(
            'INSERT INTO authority_group_siren (authority_group_id, siren) VALUES (?, ?)',
            [$groupId, $siren]
        );
    }
}
