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
        $userInfo = [
            'role' => $role->value,
            'role_str' => User::ROLES_DESCR[$role->value],
            'givenname' => 'Ada',
            'name' => 'Lovelace',
            'authority_id' => null,
            'authority_group_id' => null,
            'nb_user_with_my_certificate' => 1,
            'certificate' => (string) file_get_contents(__DIR__ . '/../lib/fixtures/user1.pem'),
        ];

        $menu = (new MenuHTML())->getMenuContent($userInfo, []);

        static::assertStringContainsString(
            '<span class="menu-identity-role">' . $role->getLabel() . '</span>',
            $menu
        );
    }
}
