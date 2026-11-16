<?php

declare(strict_types=1);

namespace IntegrationTests;

use S2low\Enum\UserRole;
use S2lowLegacy\Lib\SQLQuery;
use S2lowLegacy\Model\GroupSQL;

class GroupContactControllerTest extends S2lowIntegrationTestCase
{
    private const string URL = '/admin/mon-groupe/contact';
    private const int GROUP_ID = 1;
    private const int USER_ID = 13;
    private const string EMAIL = 'support@sm87.invalid';
    private const array CONTACT_FIELDS = ['contact_phone', 'contact_email', 'contact_hours', 'contact_message'];

    public function testGroupAdminSeesTheContactFormOfItsGroup(): void
    {
        $this->setUserWithRole(UserRole::AdministrateurGroupe);

        $page = $this->client->request('GET', self::URL)->html();

        static::assertSame(200, $this->client->getResponse()->getStatusCode());
        static::assertStringContainsString('Groupe de test', $page);
        foreach (self::CONTACT_FIELDS as $field) {
            static::assertStringContainsString('name="' . $field . '"', $page);
            static::assertStringContainsString('name="' . $field . '_shown"', $page);
        }
    }

    /**
     * @return array<string, array{0: UserRole}>
     */
    public static function roleOtherThanGroupAdminProvider(): array
    {
        return [
            'super administrateur' => [UserRole::SuperAdministrateur],
            'administrateur de collectivité' => [UserRole::AdministrateurCollectivite],
            'utilisateur' => [UserRole::Utilisateur],
            'archiviste' => [UserRole::Archiviste],
        ];
    }

    /**
     * @dataProvider roleOtherThanGroupAdminProvider
     */
    public function testOtherRolesAreForbidden(UserRole $role): void
    {
        $this->setUserWithRole($role);

        $this->client->request('GET', self::URL);

        $this->assertAccessDenied();
    }

    public function testGroupAdminWithoutGroupIsForbidden(): void
    {
        $this->setUserWithRole(UserRole::AdministrateurGroupe);
        $this->sqlQuery()->query('UPDATE users SET authority_group_id = NULL WHERE id = ?', [self::USER_ID]);

        $this->client->request('GET', self::URL);

        $this->assertAccessDenied();
    }

    public function testSavesTheContactOfTheGroupAdminGroup(): void
    {
        $this->setUserWithRole(UserRole::AdministrateurGroupe);

        $this->submitContact([
            'contact_phone' => '05 55 12 34 56',
            'contact_phone_shown' => '1',
            'contact_hours' => 'Lun-ven 9h-12h',
            'contact_hours_shown' => '1',
        ]);

        static::assertTrue($this->client->getResponse()->isRedirect(self::URL));
        $info = $this->groupInfo();
        static::assertSame('0555123456', $info['contact_phone']);
        static::assertTrue($info['contact_phone_shown']);
        static::assertSame('Lun-ven 9h-12h', $info['contact_hours']);
    }

    public function testMaskedFieldKeepsItsValue(): void
    {
        $this->setUserWithRole(UserRole::AdministrateurGroupe);

        $this->submitContact(['contact_email' => self::EMAIL]);

        $info = $this->groupInfo();
        static::assertSame(self::EMAIL, $info['contact_email']);
        static::assertFalse($info['contact_email_shown']);
    }

    public function testRefusesInvalidContactAndKeepsPreviousValues(): void
    {
        $this->setUserWithRole(UserRole::AdministrateurGroupe);
        $this->sqlQuery()->query(
            "UPDATE authority_groups SET contact_phone = '0555123456' WHERE id = ?",
            [self::GROUP_ID]
        );

        $page = $this->submitContact(['contact_phone' => '123', 'contact_phone_shown' => '1']);

        static::assertSame(422, $this->client->getResponse()->getStatusCode());
        static::assertStringContainsString('téléphone', $page);
        static::assertSame('0555123456', $this->groupInfo()['contact_phone']);
    }

    public function testRefusesTooLongHoursWithAnExplicitMessageAndKeepsWhatWasTyped(): void
    {
        $this->setUserWithRole(UserRole::AdministrateurGroupe);
        $typedHours = str_repeat('h', 81);

        $page = $this->submitContact(['contact_hours' => $typedHours, 'contact_hours_shown' => '1']);

        static::assertSame(422, $this->client->getResponse()->getStatusCode());
        static::assertStringContainsString('80 caractères', $page);
        static::assertStringContainsString("Aucune modification n'a été enregistrée", $page);
        static::assertStringContainsString('value="' . $typedHours . '"', $page);
        static::assertMatchesRegularExpression('#<input[^>]*class="form-control is-invalid"[^>]*name="contact_hours"#', $page);
        static::assertSame('', $this->groupInfo()['contact_hours']);
    }

    public function testContactFieldsDoNotSilentlyCutTheInput(): void
    {
        $this->setUserWithRole(UserRole::AdministrateurGroupe);

        $page = $this->client->request('GET', self::URL)->html();

        static::assertStringNotContainsString('maxlength', $page);
    }

    public function testRefusesSubmissionWithoutValidCsrfToken(): void
    {
        $this->setUserWithRole(UserRole::AdministrateurGroupe);

        $this->client->request('POST', self::URL, [
            '_token' => 'jeton-invalide',
            'contact_phone' => '0555123456',
            'contact_phone_shown' => '1',
        ]);

        $this->assertAccessDenied();
        static::assertSame('', $this->groupInfo()['contact_phone']);
    }

    private function assertAccessDenied(): void
    {
        static::assertTrue($this->client->getResponse()->isRedirect('/'));
    }

    /**
     * @param array<string, string> $fields
     */
    private function submitContact(array $fields): string
    {
        $token = $this->client->request('GET', self::URL)->filterXPath('//input[@name="_token"]')->attr('value');

        return $this->client->request('POST', self::URL, ['_token' => (string) $token] + $fields)->html();
    }

    /**
     * @return array<string, mixed>
     */
    private function groupInfo(): array
    {
        return self::getContainer()->get(GroupSQL::class)->getInfo(self::GROUP_ID);
    }

    private function sqlQuery(): SQLQuery
    {
        return self::getContainer()->get(SQLQuery::class);
    }
}
