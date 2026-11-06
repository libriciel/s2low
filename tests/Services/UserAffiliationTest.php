<?php

declare(strict_types=1);

namespace S2low\Tests\Services;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use S2low\DTO\GroupAffiliation;
use S2low\Enum\AdministeredModule;
use S2low\Services\UserAffiliation;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\GroupSQL;

class UserAffiliationTest extends TestCase
{
    private const int AUTHORITY_ID = 42;
    private const int ACTES_GROUP_ID = 7;
    private const int HELIOS_GROUP_ID = 8;
    private const string ACTES_GROUP_NAME = 'Groupement Haute-Vienne';
    private const string HELIOS_GROUP_NAME = 'Syndicat Limousin';

    private AuthoritySQL&MockObject $authoritySQL;
    private GroupSQL&MockObject $groupSQL;
    private UserAffiliation $userAffiliation;

    protected function setUp(): void
    {
        $this->authoritySQL = $this->createMock(AuthoritySQL::class);
        $this->groupSQL = $this->createMock(GroupSQL::class);
        $this->groupSQL->method('getInfo')->willReturnMap([
            [self::ACTES_GROUP_ID, ['id' => self::ACTES_GROUP_ID, 'name' => self::ACTES_GROUP_NAME]],
            [self::HELIOS_GROUP_ID, ['id' => self::HELIOS_GROUP_ID, 'name' => self::HELIOS_GROUP_NAME]],
        ]);

        $this->userAffiliation = new UserAffiliation($this->authoritySQL, $this->groupSQL);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function authorityRoleProvider(): array
    {
        return [
            'administrateur de collectivité' => ['ADM'],
            'utilisateur' => ['USER'],
            'archiviste' => ['ARCH'],
        ];
    }

    /**
     * @dataProvider authorityRoleProvider
     */
    public function testAuthorityMemberSeesOneGroupPerModuleWhenTheyDiffer(string $role): void
    {
        $this->authorityDesignates(self::ACTES_GROUP_ID, self::HELIOS_GROUP_ID);

        static::assertEquals(
            [
                new GroupAffiliation(self::ACTES_GROUP_NAME, [AdministeredModule::ACTES]),
                new GroupAffiliation(self::HELIOS_GROUP_NAME, [AdministeredModule::HELIOS]),
            ],
            $this->userAffiliation->getGroups($role, self::AUTHORITY_ID, null)
        );
    }

    public function testAuthorityMemberSeesTheSharedGroupOnce(): void
    {
        $this->authorityDesignates(self::ACTES_GROUP_ID, self::ACTES_GROUP_ID);

        static::assertEquals(
            [new GroupAffiliation(self::ACTES_GROUP_NAME, [AdministeredModule::ACTES, AdministeredModule::HELIOS])],
            $this->userAffiliation->getGroups('USER', self::AUTHORITY_ID, null)
        );
    }

    public function testAuthorityMemberSeesTheOnlyDesignatedGroup(): void
    {
        $this->authorityDesignates(null, self::HELIOS_GROUP_ID);

        static::assertEquals(
            [new GroupAffiliation(self::HELIOS_GROUP_NAME, [AdministeredModule::HELIOS])],
            $this->userAffiliation->getGroups('USER', self::AUTHORITY_ID, null)
        );
    }

    public function testAuthorityWithoutDesignatedGroupHasNoGroup(): void
    {
        $this->authorityDesignates(null, null);

        static::assertSame([], $this->userAffiliation->getGroups('USER', self::AUTHORITY_ID, null));
    }

    public function testUserWithoutAuthorityHasNoGroup(): void
    {
        $this->authoritySQL->expects(static::never())->method('getInfo');

        static::assertSame([], $this->userAffiliation->getGroups('USER', null, null));
    }

    public function testUnknownAuthorityHasNoGroup(): void
    {
        $this->authoritySQL->method('getInfo')->with(999)->willReturn(false);

        static::assertSame([], $this->userAffiliation->getGroups('ADM', 999, null));
    }

    public function testUnknownGroupIsLeftOut(): void
    {
        $this->authorityDesignates(self::ACTES_GROUP_ID, 999);

        static::assertEquals(
            [new GroupAffiliation(self::ACTES_GROUP_NAME, [AdministeredModule::ACTES])],
            $this->userAffiliation->getGroups('USER', self::AUTHORITY_ID, null)
        );
    }

    public function testGroupAdminSeesItsOwnGroup(): void
    {
        $this->authoritySQL->expects(static::never())->method('getInfo');

        static::assertEquals(
            [new GroupAffiliation(self::ACTES_GROUP_NAME, [])],
            $this->userAffiliation->getGroups('GADM', self::AUTHORITY_ID, self::ACTES_GROUP_ID)
        );
    }

    public function testGroupAdminWithoutGroupHasNoGroup(): void
    {
        static::assertSame([], $this->userAffiliation->getGroups('GADM', self::AUTHORITY_ID, null));
    }

    /**
     * @return array<string, array{0: string|null}>
     */
    public static function roleWithoutGroupProvider(): array
    {
        return [
            'super administrateur' => ['SADM'],
            'rôle inconnu' => ['NOPE'],
            'rôle absent' => [null],
        ];
    }

    /**
     * @dataProvider roleWithoutGroupProvider
     */
    public function testRoleWithoutGroupHasNoGroup(?string $role): void
    {
        $this->authoritySQL->expects(static::never())->method('getInfo');

        static::assertSame([], $this->userAffiliation->getGroups($role, self::AUTHORITY_ID, self::ACTES_GROUP_ID));
    }

    private function authorityDesignates(?int $actesGroupId, ?int $heliosGroupId): void
    {
        $this->authoritySQL->method('getInfo')
            ->with(self::AUTHORITY_ID)
            ->willReturn([
                'id' => self::AUTHORITY_ID,
                'actes_group_id' => $actesGroupId,
                'helios_group_id' => $heliosGroupId,
            ]);
    }
}
