<?php

declare(strict_types=1);

namespace Test\PHPUnit\Security\Authorization;

use S2low\Security\Authorization\ActesConventionAccess;
use S2low\Security\SecurityUser;
use S2lowLegacy\Lib\SQLQuery;
use S2lowLegacy\Model\UserSQL;
use S2lowTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class ActesConventionAccessTest extends S2lowTestCase
{
    private const AUTHORITY = 1;
    private const ACTES_GROUP = 1;

    private const SUPER_ADMIN = 1;
    private const ACTES_GROUP_ADMIN = 7;
    private const OTHER_GROUP_ADMIN = 10;
    private const SIMPLE_USER = 5;

    /** L'utilisateur 3 administre la collectivité 2, pas la collectivité 1. */
    private const ADMIN_OF_ANOTHER_AUTHORITY = 3;
    private const ITS_OWN_AUTHORITY = 2;

    /**
     * @dataProvider usersFacingTheConvention
     */
    public function testWhoSeesTheActesConventionOfAnAuthority(int $userId, bool $expected): void
    {
        $this->designateActesGroup(self::ACTES_GROUP);
        $this->connectAs($userId);

        static::assertSame($expected, $this->actesConventionAccess()->isVisible(self::AUTHORITY));
    }

    /**
     * @return array[]
     */
    public function usersFacingTheConvention(): array
    {
        return [
            'le super admin voit la convention' => [self::SUPER_ADMIN, true],
            'l_admin du groupe Actes voit la convention' => [self::ACTES_GROUP_ADMIN, true],
            'l_admin d_un autre groupe ne voit pas la convention' => [self::OTHER_GROUP_ADMIN, false],
            'l_admin d_une autre collectivite ne voit pas la convention' => [self::ADMIN_OF_ANOTHER_AUTHORITY, false],
            'un utilisateur simple ne voit pas la convention' => [self::SIMPLE_USER, false],
        ];
    }

    public function testTheAuthorityAdminSeesTheConventionOfItsOwnAuthority(): void
    {
        $this->connectAs(self::ADMIN_OF_ANOTHER_AUTHORITY);

        static::assertTrue($this->actesConventionAccess()->isVisible(self::ITS_OWN_AUTHORITY));
    }

    public function testNobodyConnectedSeesTheConvention(): void
    {
        $this->designateActesGroup(self::ACTES_GROUP);

        static::assertFalse($this->actesConventionAccess()->isVisible(self::AUTHORITY));
    }

    private function connectAs(int $userId): void
    {
        self::getContainer()->get('security.token_storage')->setToken(
            new UsernamePasswordToken(
                new SecurityUser(self::getContainer()->get(UserSQL::class)->getUserById($userId)),
                'main'
            )
        );
    }

    private function actesConventionAccess(): ActesConventionAccess
    {
        return self::getContainer()->get(ActesConventionAccess::class);
    }

    private function designateActesGroup(int $groupId): void
    {
        self::getContainer()->get(SQLQuery::class)->query(
            'UPDATE authorities SET actes_group_id = ? WHERE id = ?',
            [$groupId, self::AUTHORITY]
        );
    }
}
