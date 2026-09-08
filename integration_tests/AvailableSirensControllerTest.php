<?php

declare(strict_types=1);

namespace IntegrationTests;

use S2low\Enum\UserRole;
use S2lowLegacy\Lib\SQLQuery;

class AvailableSirensControllerTest extends S2lowIntegrationTestCase
{
    /**
     * @throws \Exception
     */
    public function testTheSuperAdminGetsWhatBothGroupsAllow(): void
    {
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $this->givenSirenAuthorizedForGroup(1, '491011698');
        $this->givenSirenAuthorizedForGroup(2, '491011698');
        $this->givenSirenAuthorizedForGroup(1, '111111119');

        $sirens = $this->whenTheAvailableSirensAreAsked('actes_group_id=1&helios_group_id=2');

        static::assertSame(['491011698'], $sirens);
    }

    /**
     * @throws \Exception
     */
    public function testASingleGroupGivesItsOwnSirens(): void
    {
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $this->givenSirenAuthorizedForGroup(1, '491011698');
        $this->givenSirenAuthorizedForGroup(1, '111111119');

        $sirens = $this->whenTheAvailableSirensAreAsked('actes_group_id=1');

        static::assertSame(['111111119', '491011698'], $sirens);
    }

    /**
     * La réponse expose les SIREN réservés par des groupes dont l'appelant n'est pas membre : seul
     * le super administrateur, qui choisit ces groupes, y a droit. L'application traduit un accès
     * refusé par une redirection vers l'accueil, pas par un 403.
     *
     * @throws \Exception
     */
    public function testAGroupAdminIsDenied(): void
    {
        $this->setUserWithRole(UserRole::AdministrateurGroupe);
        $this->givenSirenAuthorizedForGroup(1, '491011698');

        $this->client->request('GET', '/api/authorities/available-sirens?actes_group_id=1');

        static::assertTrue($this->client->getResponse()->isRedirection());
        static::assertStringNotContainsString('491011698', $this->client->getResponse()->getContent());
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
