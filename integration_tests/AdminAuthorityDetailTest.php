<?php

declare(strict_types=1);

namespace IntegrationTests;

use S2low\Enum\UserRole;

final class AdminAuthorityDetailTest extends S2lowIntegrationTestCase
{
    private const AUTHORITY_WITH_PASTELL_ID = 2;

    public function testDoesNotExposePastellPassword(): void
    {
        $this->setUserAuthority(self::AUTHORITY_WITH_PASTELL_ID);
        $this->setUserWithRole(UserRole::AdministrateurCollectivite);
        $_GET['id'] = (string) self::AUTHORITY_WITH_PASTELL_ID;

        $this->client->request('GET', '/admin/authorities/admin_authority_detail.php', ['id' => self::AUTHORITY_WITH_PASTELL_ID]);

        $content = $this->client->getResponse()->getContent();
        static::assertStringContainsString('"pastell_login":"pastell_login"', $content);
        static::assertStringNotContainsString('pastell_password', $content);
    }
}
