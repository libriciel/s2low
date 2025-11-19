<?php

namespace Test\PHPUnit\Security;

use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use S2low\Security\SecurityUser;
use S2low\Security\SecurityUserProvider;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

class SecurityUserProviderTest extends TestCase
{
    private function createPdoMock(): PDO
    {
        return $this->createMock(PDO::class);
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

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetch')->willReturn($userData);

        $pdo = $this->createPdoMock();
        $pdo->method('prepare')->willReturn($stmt);

        $provider = new SecurityUserProvider($pdo);
        $user = $provider->loadUserByIdentifier('42');

        $this->assertInstanceOf(SecurityUser::class, $user);
        $this->assertEquals(42, $user->getId());
    }

    public function testLoadUserByIdentifierThrowsExceptionWhenNotFound(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetch')->willReturn(false);

        $pdo = $this->createPdoMock();
        $pdo->method('prepare')->willReturn($stmt);

        $provider = new SecurityUserProvider($pdo);

        $this->expectException(UserNotFoundException::class);

        $provider->loadUserByIdentifier('999');
    }

    public function testLoadUsersByCertificateHashReturnsArray(): void
    {
        $usersData = [
            $this->createUserData(1),
            $this->createUserData(2)
        ];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn($usersData);

        $pdo = $this->createPdoMock();
        $pdo->method('prepare')->willReturn($stmt);

        $provider = new SecurityUserProvider($pdo);
        $users = $provider->loadUsersByCertificateHash('hash123');

        $this->assertCount(2, $users);
        $this->assertInstanceOf(SecurityUser::class, $users[0]);
        $this->assertInstanceOf(SecurityUser::class, $users[1]);
    }

    public function testLoadUsersByCertificateHashAndRgs2ReturnsArray(): void
    {
        $usersData = [
            $this->createUserData(1)
        ];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn($usersData);

        $pdo = $this->createPdoMock();
        $pdo->method('prepare')->willReturn($stmt);

        $provider = new SecurityUserProvider($pdo);
        $users = $provider->loadUsersByCertificateHashAndRgs2('hash123', 'rgs2cert');

        $this->assertCount(1, $users);
        $this->assertInstanceOf(SecurityUser::class, $users[0]);
    }

    public function testLoadUserByCertificateAndLoginReturnsArray(): void
    {
        $usersData = [
            $this->createUserData(1)
        ];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn($usersData);

        $pdo = $this->createPdoMock();
        $pdo->method('prepare')->willReturn($stmt);

        $provider = new SecurityUserProvider($pdo);
        $users = $provider->loadUserByCertificateAndLogin('hash123', 'rgs2cert', 'user1');

        $this->assertCount(1, $users);
        $this->assertInstanceOf(SecurityUser::class, $users[0]);
    }

    public function testLoadUserByCertificateAndAuthorityReturnsUser(): void
    {
        $userData = $this->createUserData(1);

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetch')->willReturn($userData);

        $pdo = $this->createPdoMock();
        $pdo->method('prepare')->willReturn($stmt);

        $provider = new SecurityUserProvider($pdo);
        $user = $provider->loadUserByCertificateAndAuthority('hash123', 1);

        $this->assertInstanceOf(SecurityUser::class, $user);
        $this->assertEquals(1, $user->getId());
    }

    public function testLoadUserByCertificateAndAuthorityReturnsNullWhenNotFound(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetch')->willReturn(false);

        $pdo = $this->createPdoMock();
        $pdo->method('prepare')->willReturn($stmt);

        $provider = new SecurityUserProvider($pdo);
        $user = $provider->loadUserByCertificateAndAuthority('hash123', 999);

        $this->assertNull($user);
    }

    public function testRefreshUserReturnsUpdatedUser(): void
    {
        $userData = $this->createUserData(42);

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetch')->willReturn($userData);

        $pdo = $this->createPdoMock();
        $pdo->method('prepare')->willReturn($stmt);

        $provider = new SecurityUserProvider($pdo);

        $existingUser = new SecurityUser($userData);
        $refreshedUser = $provider->refreshUser($existingUser);

        $this->assertInstanceOf(SecurityUser::class, $refreshedUser);
        $this->assertEquals(42, $refreshedUser->getId());
    }

    public function testRefreshUserThrowsExceptionForInvalidUserClass(): void
    {
        $pdo = $this->createPdoMock();
        $provider = new SecurityUserProvider($pdo);

        $invalidUser = $this->createMock(\Symfony\Component\Security\Core\User\UserInterface::class);

        $this->expectException(UnsupportedUserException::class);

        $provider->refreshUser($invalidUser);
    }

    public function testRefreshUserThrowsExceptionWhenUserNotFound(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetch')->willReturn(false);

        $pdo = $this->createPdoMock();
        $pdo->method('prepare')->willReturn($stmt);

        $provider = new SecurityUserProvider($pdo);

        $userData = $this->createUserData(999);
        $existingUser = new SecurityUser($userData);

        $this->expectException(UserNotFoundException::class);

        $provider->refreshUser($existingUser);
    }

    public function testSupportsClassReturnsTrueForSecurityUser(): void
    {
        $pdo = $this->createPdoMock();
        $provider = new SecurityUserProvider($pdo);

        $this->assertTrue($provider->supportsClass(SecurityUser::class));
    }

    public function testSupportsClassReturnsFalseForOtherClasses(): void
    {
        $pdo = $this->createPdoMock();
        $provider = new SecurityUserProvider($pdo);

        $this->assertFalse($provider->supportsClass(\stdClass::class));
    }
}
