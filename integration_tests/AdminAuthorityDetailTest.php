<?php

declare(strict_types=1);

namespace IntegrationTests;

use S2low\Enum\UserRole;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Lib\SQLQuery;

final class AdminAuthorityDetailTest extends S2lowIntegrationTestCase
{
    private const AUTHORITY_WITH_PASTELL_ID = 2;
    private const int AUTHORITY_ID = 1;
    private const int GROUP_OF_THE_ADMIN = 1;
    private const int OTHER_GROUP = 2;

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

    public function testTheDetailHidesHeliosFromTheAdminOfTheActesGroupOnly(): void
    {
        $this->givenTheAuthorityAdministeredBy(self::GROUP_OF_THE_ADMIN, self::OTHER_GROUP);
        $this->setUserWithRole(UserRole::AdministrateurGroupe);

        $detail = $this->whenTheDetailIsRead();

        static::assertArrayNotHasKey('helios_ftp_dest', $detail);
        static::assertArrayNotHasKey(Module::HELIOS, $detail['module']);
        static::assertArrayHasKey(Module::ACTES, $detail['module']);
    }

    public function testTheDetailHidesTheActesModuleFromTheAdminOfTheHeliosGroupOnly(): void
    {
        $this->givenTheAuthorityAdministeredBy(self::OTHER_GROUP, self::GROUP_OF_THE_ADMIN);
        $this->setUserWithRole(UserRole::AdministrateurGroupe);

        $detail = $this->whenTheDetailIsRead();

        static::assertSame('helios_ftp_dest', $detail['helios_ftp_dest']);
        static::assertArrayHasKey(Module::HELIOS, $detail['module']);
        static::assertArrayNotHasKey(Module::ACTES, $detail['module']);
    }

    public function testTheSuperAdminSeesTheWholeDetail(): void
    {
        $this->givenTheAuthorityAdministeredBy(self::OTHER_GROUP, self::OTHER_GROUP);
        $this->setUserWithRole(UserRole::SuperAdministrateur);

        $detail = $this->whenTheDetailIsRead();

        static::assertSame('helios_ftp_dest', $detail['helios_ftp_dest']);
        static::assertArrayHasKey(Module::ACTES, $detail['module']);
        static::assertArrayHasKey(Module::HELIOS, $detail['module']);
    }

    public function testAnIdentifierThatIsNotANumberIsRefusedWithAReadableMessage(): void
    {
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $_GET['id'] = 'SGER';

        $this->client->request('GET', '/admin/authorities/admin_authority_detail.php', ['id' => 'SGER']);

        $content = (string) $this->client->getResponse()->getContent();
        static::assertSame(1, preg_match('#\{"status":"error"[^}]*\}#', $content, $errorJson), $content);
        $error = json_decode($errorJson[0], true, flags: JSON_THROW_ON_ERROR);

        static::assertSame("L'identifiant de collectivité « SGER » n'est pas un nombre", $error['error-message']);
        static::assertStringNotContainsString('SQLSTATE', $content);
    }

    private function givenTheAuthorityAdministeredBy(int $actesGroupId, int $heliosGroupId): void
    {
        self::getContainer()->get(SQLQuery::class)->query(
            'UPDATE authorities SET actes_group_id = ?, helios_group_id = ? WHERE id = ?',
            [$actesGroupId, $heliosGroupId, self::AUTHORITY_ID]
        );
    }

    private function whenTheDetailIsRead(): array
    {
        $_GET['id'] = (string) self::AUTHORITY_ID;
        $this->client->request('GET', '/admin/authorities/admin_authority_detail.php', ['id' => self::AUTHORITY_ID]);

        $content = (string) $this->client->getResponse()->getContent();

        // La réponse porte aussi l'avertissement de xdebug sur utf8_decode() : on n'en garde que le JSON.
        return json_decode(substr($content, (int) strrpos($content, '{"id":')), true, flags: JSON_THROW_ON_ERROR);
    }
}
