<?php

namespace IntegrationTests\EndpointsActe;

use IntegrationTests\S2lowIntegrationTestCase;
use S2lowLegacy\Class\User;

class AdminIndexTest extends S2lowIntegrationTestCase
{
    const ADMIN_INDEX = 'modules/actes/admin/index.php';

    public static function rolesButNotSADMProvider(): \Generator
    {
        yield 'En tant que Utilisateur' => [User::USER];
        yield 'En tant que Administrateur' => [User::ADM];
        yield 'En tant que Arch' => [User::ARCH];
        yield 'En tant que Gadm' => [User::GADM];
    }

    public static function rolesSADMProvider(): \Generator
    {
        yield 'En tant que Super Administrateur' => [User::SADM];
    }

    /**
     * @dataProvider rolesButNotSADMProvider
     * @dataProvider rolesSADMProvider
     */
    public function testShouldReturnSuccessResponse(string $role): void
    {
        $client = $this->getAuthenticatedClientWithUserLoggedAs($role);
        $client->request('GET', self::ADMIN_INDEX);

        static::assertResponseIsSuccessful();
    }

    public function testShouldReturnRightContentWhenAuthenticatedAsSuperAdmin(): void
    {
        $client = $this->getAuthenticatedClientWithUserLoggedAs(User::SADM);
        $client->request('GET', self::ADMIN_INDEX);

        $response = $client->getResponse();

        static::assertStringContainsString("Administration", $response->getContent());
    }


    /**
     * @dataProvider rolesButNotSADMProvider
     */
    public function testShouldDenyAccessWhenNotAuthenticatedAsAdmin(string $role): void
    {
        $client = $this->getAuthenticatedClientWithUserLoggedAs($role);
        $client->request('GET', self::ADMIN_INDEX);

        $response = $client->getResponse();

        static::assertStringNotContainsString("Administration", $response->getContent());
    }
}