<?php

declare(strict_types=1);

namespace IntegrationTests;

use S2low\Enum\UserRole;
use S2lowLegacy\Lib\SQLQuery;

class AdminAuthorityEditTest extends S2lowIntegrationTestCase
{
    /**
     * @throws \Exception
     */
    public function testTheSuperAdminChoosesTheGroupOfEachAdministeredModule(): void
    {
        $this->setUserWithRole(UserRole::SuperAdministrateur);

        $page = $this->whenTheAuthorityIsEdited();

        static::assertStringContainsString('name="actes_group_id"', $page);
        static::assertStringContainsString('name="helios_group_id"', $page);
    }

    /**
     * Les réglages d'un module tiennent dans son propre bloc, avec la case qui l'active.
     *
     * @throws \Exception
     */
    public function testEachAdministeredModuleGathersItsOwnSettings(): void
    {
        $this->setUserWithRole(UserRole::SuperAdministrateur);

        $page = $this->whenTheAuthorityIsEdited();

        static::assertStringContainsString('Module Actes', $page);
        static::assertStringContainsString('Module Helios', $page);
        static::assertStringContainsString('name="convention_actes"', $page);
        static::assertStringContainsString('name="helios_ftp_dest"', $page);
    }

    /**
     * Une liste de SIREN vide n'explique rien à qui cherche à reprendre celui d'une autre
     * collectivité.
     *
     * @throws \Exception
     */
    public function testAnEmptySirenListSaysWhy(): void
    {
        $this->setUserWithRole(UserRole::AdministrateurGroupe);
        $this->givenAuthority1AdministeredByGroup1();

        $page = $this->whenTheAuthorityIsEdited();

        static::assertStringContainsString('Aucun SIREN disponible', $page);
    }

    /**
     * @throws \Exception
     */
    public function testAGroupAdminCannotChooseAnyGroup(): void
    {
        $this->setUserWithRole(UserRole::AdministrateurGroupe);
        $this->givenAuthority1AdministeredByGroup1();

        $page = $this->whenTheAuthorityIsEdited();

        static::assertStringNotContainsString('name="actes_group_id"', $page);
        static::assertStringNotContainsString('name="helios_group_id"', $page);
        static::assertStringContainsString('Groupe administrateur', $page);
    }

    /**
     * @throws \Exception
     */
    private function whenTheAuthorityIsEdited(): string
    {
        $_GET = ['id' => '1'];

        return $this->client->request('GET', '/admin/authorities/admin_authority_edit.php?id=1')->html();
    }

    private function givenAuthority1AdministeredByGroup1(): void
    {
        self::getContainer()->get(SQLQuery::class)->query(
            'UPDATE authorities SET actes_group_id = 1, helios_group_id = 1 WHERE id = 1'
        );
    }
}
