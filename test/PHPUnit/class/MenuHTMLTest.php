<?php

declare(strict_types=1);

namespace PHPUnit\class;

use Generator;
use S2low\Enum\UserRole;
use S2lowLegacy\Class\MenuHTML;
use S2lowLegacy\Class\User;
use S2lowTestCase;

class MenuHTMLTest extends S2lowTestCase
{
    private const int AUTHORITY_ID = 1;
    private const string AUTHORITY_NAME = 'Bourg-en-Bresse';
    private const string GROUP_NAME = 'Groupe de test';
    private const int GROUP_ID = 1;

    public static function roleProvider(): Generator
    {
        foreach (UserRole::cases() as $role) {
            yield $role->name => [$role];
        }
    }

    /**
     * @dataProvider roleProvider
     */
    public function testShowsTheSameRoleLabelAsTheTwigMenu(UserRole $role): void
    {
        $menu = (new MenuHTML())->getMenuContent($this->userInfo($role, null), []);

        static::assertStringContainsString(
            '<span class="menu-identity-role">' . $role->getLabel() . '</span>',
            $menu
        );
    }

    /**
     * @dataProvider authorityRoleProvider
     */
    public function testShowsOnlyTheGroupsAdministeringTheAuthority(UserRole $role): void
    {
        $menu = (new MenuHTML())->getMenuContent($this->userInfo($role, self::AUTHORITY_ID), []);

        static::assertStringContainsString(
            '<span class="menu-affiliation-caption">Groupe Actes / Helios</span>',
            $menu
        );
        static::assertStringContainsString(
            '<span class="menu-affiliation-name">' . self::GROUP_NAME . '</span>',
            $menu
        );
        static::assertStringNotContainsString(self::AUTHORITY_NAME, $menu);
    }

    public static function authorityRoleProvider(): Generator
    {
        yield 'administrateur de collectivité' => [UserRole::AdministrateurCollectivite];
        yield 'utilisateur' => [UserRole::Utilisateur];
        yield 'archiviste' => [UserRole::Archiviste];
    }

    public function testGroupAdminSeesLinkToItsGroupContact(): void
    {
        $userInfo = ['authority_group_id' => self::GROUP_ID] + $this->userInfo(UserRole::AdministrateurGroupe, self::AUTHORITY_ID);

        $menu = (new MenuHTML())->getMenuContent($userInfo, []);

        static::assertMatchesRegularExpression(
            '#href="[^"]*/admin/mon-groupe/contact">Assistance utilisateurs</a>#',
            $menu
        );
    }

    public static function roleWithoutGroupContactLinkProvider(): Generator
    {
        yield 'super administrateur' => [UserRole::SuperAdministrateur];
        yield 'administrateur de collectivité' => [UserRole::AdministrateurCollectivite];
        yield 'utilisateur' => [UserRole::Utilisateur];
    }

    /**
     * @dataProvider roleWithoutGroupContactLinkProvider
     */
    public function testOtherRolesHaveNoGroupContactLink(UserRole $role): void
    {
        $userInfo = ['authority_group_id' => self::GROUP_ID] + $this->userInfo($role, self::AUTHORITY_ID);

        $menu = (new MenuHTML())->getMenuContent($userInfo, []);

        static::assertStringNotContainsString('Assistance utilisateurs', $menu);
    }

    /**
     * @return array<string, mixed>
     */
    private function userInfo(UserRole $role, ?int $authorityId): array
    {
        return [
            'role' => $role->value,
            'role_str' => User::ROLES_DESCR[$role->value],
            'givenname' => 'Ada',
            'name' => 'Lovelace',
            'authority_id' => $authorityId,
            'authority_group_id' => null,
            'nb_user_with_my_certificate' => 1,
            'certificate' => (string) file_get_contents(__DIR__ . '/../lib/fixtures/user1.pem'),
        ];
    }
}
