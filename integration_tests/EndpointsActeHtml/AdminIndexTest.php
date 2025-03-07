<?php

namespace IntegrationTests\EndpointsActeHtml;

use IntegrationTests\S2lowIntegrationTestCase;
use S2low\Enum\UserRole;

class AdminIndexTest extends S2lowIntegrationTestCase
{
    const ADMIN_INDEX = 'modules/actes/admin/index.php';

    public static function rolesButNotSADMProvider(): \Generator
    {
        yield 'En tant que Utilisateur' => [UserRole::Utilisateur];
        yield 'En tant que Administrateur' => [UserRole::AdministrateurCollectivite];
        yield 'En tant que Arch' => [UserRole::Archiviste];
        yield 'En tant que Gadm' => [UserRole::AdministrateurGroupe];
    }

    public static function rolesSADMProvider(): \Generator
    {
        yield 'En tant que Super Administrateur' => [UserRole::SuperAdministrateur];
    }

    /**
     * @dataProvider rolesButNotSADMProvider
     * @dataProvider rolesSADMProvider
     */
    public function testShouldReturnSuccessResponse(UserRole $role): void
    {
        $client = $this->getAuthenticatedClientWithUserLoggedAs($role);
        $client->request('GET', self::ADMIN_INDEX);

        static::assertResponseIsSuccessful();
    }

    public function testShouldReturnRightContentWhenAuthenticatedAsSuperAdmin(): void
    {
        $client = $this->getAuthenticatedClientWithUserLoggedAs(UserRole::SuperAdministrateur);
        $client->request('GET', self::ADMIN_INDEX);

        $response = $client->getResponse();

        static::assertStringContainsString("Administration", $response->getContent());
    }


    /**
     * @dataProvider rolesButNotSADMProvider
     */
    public function testShouldDenyAccessWhenNotAuthenticatedAsAdmin(UserRole $role): void
    {
        $client = $this->getAuthenticatedClientWithUserLoggedAs($role);
        $client->request('GET', self::ADMIN_INDEX);

        $response = $client->getResponse();

        static::assertStringNotContainsString("Administration", $response->getContent());
    }
}