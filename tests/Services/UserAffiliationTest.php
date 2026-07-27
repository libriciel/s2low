<?php

declare(strict_types=1);

namespace S2low\Tests\Services;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use S2low\Services\UserAffiliation;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\GroupSQL;

class UserAffiliationTest extends TestCase
{
    private AuthoritySQL&MockObject $authoritySQL;
    private GroupSQL&MockObject $groupSQL;
    private UserAffiliation $userAffiliation;

    protected function setUp(): void
    {
        $this->authoritySQL = $this->createMock(AuthoritySQL::class);
        $this->groupSQL = $this->createMock(GroupSQL::class);

        $this->userAffiliation = new UserAffiliation($this->authoritySQL, $this->groupSQL);
    }

    /**
     * @return array<string, array{0: string|null, 1: string|null}>
     */
    public static function typeProvider(): array
    {
        return [
            'super administrateur' => ['SADM', null],
            'administrateur de groupe' => ['GADM', UserAffiliation::TYPE_GROUP],
            'administrateur de collectivité' => ['ADM', UserAffiliation::TYPE_AUTHORITY],
            'utilisateur' => ['USER', UserAffiliation::TYPE_AUTHORITY],
            'archiviste' => ['ARCH', UserAffiliation::TYPE_AUTHORITY],
            'rôle inconnu' => ['NOPE', null],
            'rôle absent' => [null, null],
        ];
    }

    /**
     * @dataProvider typeProvider
     */
    public function testTypeDependsOnRole(?string $role, ?string $expectedType): void
    {
        static::assertSame($expectedType, $this->userAffiliation->getType($role));
    }

    public function testSuperAdminHasNoAffiliation(): void
    {
        $this->authoritySQL->expects(static::never())->method('getInfo');
        $this->groupSQL->expects(static::never())->method('getInfo');

        static::assertNull($this->userAffiliation->getName('SADM', 42, 7));
    }

    public function testAuthorityAdminIsAffiliatedToItsAuthority(): void
    {
        $this->authoritySQL->method('getInfo')
            ->with(42)
            ->willReturn(['id' => 42, 'name' => 'Mairie de Saint-Just-le-Martel']);

        static::assertSame(
            'Mairie de Saint-Just-le-Martel',
            $this->userAffiliation->getName('ADM', 42, null)
        );
    }

    public function testUserIsAffiliatedToItsAuthority(): void
    {
        $this->authoritySQL->method('getInfo')
            ->with(42)
            ->willReturn(['id' => 42, 'name' => 'Mairie de Saint-Just-le-Martel']);

        static::assertSame(
            'Mairie de Saint-Just-le-Martel',
            $this->userAffiliation->getName('USER', 42, null)
        );
    }

    public function testGroupAdminIsAffiliatedToItsGroup(): void
    {
        $this->authoritySQL->expects(static::never())->method('getInfo');
        $this->groupSQL->method('getInfo')
            ->with(7)
            ->willReturn(['id' => 7, 'name' => 'Groupement Haute-Vienne']);

        static::assertSame(
            'Groupement Haute-Vienne',
            $this->userAffiliation->getName('GADM', 42, 7)
        );
    }

    public function testGroupAdminWithoutGroupHasNoAffiliation(): void
    {
        $this->groupSQL->expects(static::never())->method('getInfo');

        static::assertNull($this->userAffiliation->getName('GADM', 42, null));
    }

    public function testUserWithoutAuthorityHasNoAffiliation(): void
    {
        $this->authoritySQL->expects(static::never())->method('getInfo');

        static::assertNull($this->userAffiliation->getName('USER', 0, null));
    }

    public function testUnknownAuthorityHasNoAffiliation(): void
    {
        $this->authoritySQL->method('getInfo')->with(999)->willReturn(false);

        static::assertNull($this->userAffiliation->getName('ADM', 999, null));
    }
}
