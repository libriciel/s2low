<?php

namespace Test\PHPUnit\Security;

use PHPUnit\Framework\TestCase;
use S2low\Security\PasswordUserProvider;
use S2low\Security\SecurityUser;
use S2lowLegacy\Model\UserSQL;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Tests pour PasswordUserProvider
 *
 * @see docs/AUTHENTICATION.md Documentation complète du système d'authentification
 */
class PasswordUserProviderTest extends TestCase
{
    private function createProvider(?UserSQL $userSQL = null): PasswordUserProvider
    {
        $userSQL = $userSQL ?? $this->createMock(UserSQL::class);

        return new PasswordUserProvider($userSQL);
    }

    private function createUserData(): array
    {
        return [
            'id' => 1,
            'email' => 'test@test.com',
            'login' => 'testuser',
            'password' => '$2y$13$hashed_password',
            'role' => 'USER',
            'authority_id' => 1,
            'authority_group_id' => null,
            'status' => 1,
            'certificate_hash' => null,
            'name' => 'Test',
            'givenname' => 'User'
        ];
    }

    public function testLoadUserByIdentifierReturnsSecurityUser(): void
    {
        $userData = $this->createUserData();

        $userSQL = $this->createMock(UserSQL::class);
        $userSQL->expects($this->once())
            ->method('queryOne')
            ->with(
                $this->stringContains('SELECT'),
                'testuser'
            )
            ->willReturn($userData);

        $provider = $this->createProvider($userSQL);
        $user = $provider->loadUserByIdentifier('testuser');

        $this->assertInstanceOf(SecurityUser::class, $user);
        $this->assertEquals('1', $user->getUserIdentifier());
        $this->assertEquals('testuser', $user->getLogin());
        $this->assertEquals('test@test.com', $user->getEmail());
    }

    public function testLoadUserByIdentifierThrowsExceptionWhenUserNotFound(): void
    {
        $userSQL = $this->createMock(UserSQL::class);
        $userSQL->expects($this->once())
            ->method('queryOne')
            ->with(
                $this->stringContains('SELECT'),
                'nonexistent'
            )
            ->willReturn(false);

        $provider = $this->createProvider($userSQL);

        $this->expectException(UserNotFoundException::class);
        $this->expectExceptionMessage('Utilisateur avec le login "nonexistent" introuvable');

        $provider->loadUserByIdentifier('nonexistent');
    }

    public function testRefreshUserReloadsUserFromDatabase(): void
    {
        $userData = $this->createUserData();
        $user = new SecurityUser($userData);

        $userSQL = $this->createMock(UserSQL::class);
        $userSQL->expects($this->once())
            ->method('queryOne')
            ->with(
                $this->stringContains('SELECT'),
                1
            )
            ->willReturn($userData);

        $provider = $this->createProvider($userSQL);
        $refreshedUser = $provider->refreshUser($user);

        $this->assertInstanceOf(SecurityUser::class, $refreshedUser);
        $this->assertEquals('1', $refreshedUser->getUserIdentifier());
    }

    public function testRefreshUserThrowsExceptionForUnsupportedUserClass(): void
    {
        $user = $this->createMock(UserInterface::class);

        $provider = $this->createProvider();

        $this->expectException(UnsupportedUserException::class);

        $provider->refreshUser($user);
    }

    public function testRefreshUserThrowsExceptionWhenUserNotFoundInDatabase(): void
    {
        $userData = $this->createUserData();
        $user = new SecurityUser($userData);

        $userSQL = $this->createMock(UserSQL::class);
        $userSQL->expects($this->once())
            ->method('queryOne')
            ->with(
                $this->stringContains('SELECT'),
                1
            )
            ->willReturn(false);

        $provider = $this->createProvider($userSQL);

        $this->expectException(UserNotFoundException::class);
        $this->expectExceptionMessage('Utilisateur avec ID "1" introuvable');

        $provider->refreshUser($user);
    }

    public function testSupportsClassReturnsTrueForSecurityUser(): void
    {
        $provider = $this->createProvider();

        $this->assertTrue($provider->supportsClass(SecurityUser::class));
    }

    public function testSupportsClassReturnsFalseForOtherClasses(): void
    {
        $provider = $this->createProvider();

        $this->assertFalse($provider->supportsClass(UserInterface::class));
        $this->assertFalse($provider->supportsClass(\stdClass::class));
    }

    public function testUpgradePasswordUpdatesUserPassword(): void
    {
        $userData = $this->createUserData();
        $user = new SecurityUser($userData);
        $newHashedPassword = '$2y$13$new_hashed_password';

        $userSQL = $this->createMock(UserSQL::class);
        $userSQL->expects($this->once())
            ->method('query')
            ->with(
                $this->stringContains('UPDATE users SET password'),
                [$newHashedPassword, 1]
            );

        $provider = $this->createProvider($userSQL);
        $provider->upgradePassword($user, $newHashedPassword);
    }
}
