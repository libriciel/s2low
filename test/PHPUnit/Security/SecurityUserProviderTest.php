<?php

namespace Test\PHPUnit\Security;

use PHPUnit\Framework\TestCase;
use S2low\Security\SecurityUser;
use S2low\Security\SecurityUserProvider;
use S2lowLegacy\Model\UserSQL;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

class SecurityUserProviderTest extends TestCase
{
    private function createUserSQLMock(): UserSQL
    {
        return $this->createMock(UserSQL::class);
    }

    private function createUserData(int $id = 1): array
    {
        return [
            'id' => $id,
            'email' => "user{$id}@test.com",
            'login' => "user{$id}",
            'password' => 'hashed_password',
            'role' => 'USER',
            'authority_id' => 1,
            'authority_group_id' => null,
            'status' => 1,
            'certificate_hash' => 'hash123',
            'name' => 'Test',
            'givenname' => 'User'
        ];
    }

    public function testLoadUserByIdentifierReturnsUser(): void
    {
        $userData = $this->createUserData(42);

        $userSQL = $this->createUserSQLMock();
        $userSQL->method('getUserById')->willReturn($userData);

        $provider = new SecurityUserProvider($userSQL);
        $user = $provider->loadUserByIdentifier('42');

        $this->assertInstanceOf(SecurityUser::class, $user);
        $this->assertEquals(42, $user->getId());
    }

    public function testLoadUserByIdentifierThrowsExceptionWhenNotFound(): void
    {
        $userSQL = $this->createUserSQLMock();
        $userSQL->method('queryOne')->willReturn(false);

        $provider = new SecurityUserProvider($userSQL);

        $this->expectException(UserNotFoundException::class);

        $provider->loadUserByIdentifier('999');
    }

    public function testLoadUsersByCertificateHashReturnsArray(): void
    {
        $usersData = [
            $this->createUserData(1),
            $this->createUserData(2)
        ];

        $userSQL = $this->createUserSQLMock();
        $userSQL->method('getUserFromCertificatHash')->willReturn($usersData);

        $provider = new SecurityUserProvider($userSQL);
        $users = $provider->loadUsersByCertificateHash('hash123');

        $this->assertCount(2, $users);
        $this->assertInstanceOf(SecurityUser::class, $users[0]);
        $this->assertInstanceOf(SecurityUser::class, $users[1]);
    }

    public function testLoadUserByCertificateAndLoginUser(): void
    {
        $userData = $this->createUserData(1);

        $userSQL = $this->createUserSQLMock();
        $userSQL->method('getUserByCertificatsAndLogin')->willReturn($userData);

        $provider = new SecurityUserProvider($userSQL);
        $user = $provider->loadUserByCertificateAndLogin('hash123', 'user1');

        $this->assertInstanceOf(SecurityUser::class, $user);
    }

    public function testLoadUserByCertificateAndAuthorityReturnsUser(): void
    {
        $userData = $this->createUserData(1);

        $userSQL = $this->createUserSQLMock();
        $userSQL->method('getUserByCertificatAndAuthority')->willReturn($userData);

        $provider = new SecurityUserProvider($userSQL);
        $user = $provider->loadUserByCertificateAndAuthority('hash123', 1);

        $this->assertInstanceOf(SecurityUser::class, $user);
        $this->assertEquals(1, $user->getId());
    }

    public function testLoadUserByCertificateAndAuthorityReturnsNullWhenNotFound(): void
    {
        $userSQL = $this->createUserSQLMock();
        $userSQL->method('queryOne')->willReturn(false);

        $provider = new SecurityUserProvider($userSQL);
        $this->expectException(UserNotFoundException::class);

        $provider->loadUserByCertificateAndAuthority('hash123', 999);
    }

    public function testRefreshUserReturnsUpdatedUser(): void
    {
        $userData = $this->createUserData(42);

        $userSQL = $this->createUserSQLMock();
        $userSQL->method('getUserById')->willReturn($userData);

        $provider = new SecurityUserProvider($userSQL);

        $existingUser = new SecurityUser($userData);
        $refreshedUser = $provider->refreshUser($existingUser);

        $this->assertInstanceOf(SecurityUser::class, $refreshedUser);
        $this->assertEquals(42, $refreshedUser->getId());
    }

    public function testRefreshUserThrowsExceptionForInvalidUserClass(): void
    {
        $userSQL = $this->createUserSQLMock();
        $provider = new SecurityUserProvider($userSQL);

        $invalidUser = $this->createMock(\Symfony\Component\Security\Core\User\UserInterface::class);

        $this->expectException(UnsupportedUserException::class);

        $provider->refreshUser($invalidUser);
    }

    public function testRefreshUserThrowsExceptionWhenUserNotFound(): void
    {
        $userSQL = $this->createUserSQLMock();
        $userSQL->method('queryOne')->willReturn(false);

        $provider = new SecurityUserProvider($userSQL);

        $userData = $this->createUserData(999);
        $existingUser = new SecurityUser($userData);

        $this->expectException(UserNotFoundException::class);

        $provider->refreshUser($existingUser);
    }

    public function testSupportsClassReturnsTrueForSecurityUser(): void
    {
        $userSQL = $this->createUserSQLMock();
        $provider = new SecurityUserProvider($userSQL);

        $this->assertTrue($provider->supportsClass(SecurityUser::class));
    }

    public function testSupportsClassReturnsFalseForOtherClasses(): void
    {
        $userSQL = $this->createUserSQLMock();
        $provider = new SecurityUserProvider($userSQL);

        $this->assertFalse($provider->supportsClass(\stdClass::class));
    }
}
