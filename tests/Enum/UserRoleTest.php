<?php

declare(strict_types=1);

namespace S2low\Tests\Enum;

use PHPUnit\Framework\TestCase;
use S2low\Enum\UserRole;

class UserRoleTest extends TestCase
{
    /**
     * @dataProvider roleParsingProvider
     */
    public function testFromRole(string $input, UserRole $expected): void
    {
        self::assertSame($expected, UserRole::fromRole($input));
    }

    public function roleParsingProvider(): array
    {
        return [
            ['ROLE_SADM', UserRole::SuperAdministrateur],
            ['ROLE_GADM', UserRole::AdministrateurGroupe],
            ['ROLE_ADM', UserRole::AdministrateurCollectivite],
            ['ROLE_ARCH', UserRole::Archiviste],
            ['ROLE_USER', UserRole::Utilisateur],
            ['SADM', UserRole::SuperAdministrateur],
            ['GADM', UserRole::AdministrateurGroupe],
            ['ADM', UserRole::AdministrateurCollectivite],
            ['ARCH', UserRole::Archiviste],
            ['USER', UserRole::Utilisateur],
        ];
    }

    public function testRoleCheckMethods(): void
    {
        // Super Admin
        $sadm = UserRole::SuperAdministrateur;
        self::assertTrue($sadm->isSuperAdmin());
        self::assertFalse($sadm->isGroupAdmin());
        self::assertFalse($sadm->isAuthorityAdmin());
        self::assertFalse($sadm->isArchivist());
        self::assertFalse($sadm->isUser());
        self::assertTrue($sadm->isAnyAdmin());
        self::assertTrue($sadm->isGroupOrSuperAdmin());

        // Group Admin
        $gadm = UserRole::AdministrateurGroupe;
        self::assertFalse($gadm->isSuperAdmin());
        self::assertTrue($gadm->isGroupAdmin());
        self::assertFalse($gadm->isAuthorityAdmin());
        self::assertFalse($gadm->isArchivist());
        self::assertFalse($gadm->isUser());
        self::assertTrue($gadm->isAnyAdmin());
        self::assertTrue($gadm->isGroupOrSuperAdmin());

        // Authority Admin
        $adm = UserRole::AdministrateurCollectivite;
        self::assertFalse($adm->isSuperAdmin());
        self::assertFalse($adm->isGroupAdmin());
        self::assertTrue($adm->isAuthorityAdmin());
        self::assertFalse($adm->isArchivist());
        self::assertFalse($adm->isUser());
        self::assertTrue($adm->isAnyAdmin());
        self::assertFalse($adm->isGroupOrSuperAdmin());

        // Archivist
        $arch = UserRole::Archiviste;
        self::assertFalse($arch->isSuperAdmin());
        self::assertFalse($arch->isGroupAdmin());
        self::assertFalse($arch->isAuthorityAdmin());
        self::assertTrue($arch->isArchivist());
        self::assertFalse($arch->isUser());
        self::assertFalse($arch->isAnyAdmin());
        self::assertFalse($arch->isGroupOrSuperAdmin());

        // User
        $user = UserRole::Utilisateur;
        self::assertFalse($user->isSuperAdmin());
        self::assertFalse($user->isGroupAdmin());
        self::assertFalse($user->isAuthorityAdmin());
        self::assertFalse($user->isArchivist());
        self::assertTrue($user->isUser());
        self::assertFalse($user->isAnyAdmin());
        self::assertFalse($user->isGroupOrSuperAdmin());
    }
}
