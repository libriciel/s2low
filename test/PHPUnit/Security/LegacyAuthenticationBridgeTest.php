<?php

namespace Test\PHPUnit\Security;

use PHPUnit\Framework\TestCase;
use S2low\Security\LegacyAuthenticationBridge;
use S2low\Security\SecurityUser;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class LegacyAuthenticationBridgeTest extends TestCase
{
    public function testGetAuthenticatedUserIdReturnsNullWhenNoToken(): void
    {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn(null);

        $bridge = new LegacyAuthenticationBridge($tokenStorage);

        $this->assertNull($bridge->getAuthenticatedUserId());
    }

    public function testGetAuthenticatedUserIdReturnsNullWhenUserNotSecurityUser(): void
    {
        // Créer un mock d'un UserInterface qui n'est pas un SecurityUser
        $otherUser = $this->createMock(\Symfony\Component\Security\Core\User\UserInterface::class);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($otherUser);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        $bridge = new LegacyAuthenticationBridge($tokenStorage);

        $this->assertNull($bridge->getAuthenticatedUserId());
    }

    public function testGetAuthenticatedUserIdReturnsUserIdWhenAuthenticated(): void
    {
        $user = new SecurityUser([
            'id' => 42,
            'email' => 'test@test.com',
            'login' => null,
            'password' => null,
            'role' => 'USER',
            'authority_id' => 1,
            'status' => 1,
            'certificate_hash' => 'hash123',
            'name' => 'Test',
            'givenname' => 'User'
        ]);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        $bridge = new LegacyAuthenticationBridge($tokenStorage);

        $this->assertEquals(42, $bridge->getAuthenticatedUserId());
    }

    public function testGetAuthenticatedUserReturnsNullWhenNoToken(): void
    {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn(null);

        $bridge = new LegacyAuthenticationBridge($tokenStorage);

        $this->assertNull($bridge->getAuthenticatedUser());
    }

    public function testGetAuthenticatedUserReturnsUserWhenAuthenticated(): void
    {
        $user = new SecurityUser([
            'id' => 42,
            'email' => 'test@test.com',
            'login' => null,
            'password' => null,
            'role' => 'USER',
            'authority_id' => 1,
            'status' => 1,
            'certificate_hash' => 'hash123',
            'name' => 'Test',
            'givenname' => 'User'
        ]);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        $bridge = new LegacyAuthenticationBridge($tokenStorage);

        $this->assertSame($user, $bridge->getAuthenticatedUser());
    }

    public function testIsAuthenticatedReturnsFalseWhenNoToken(): void
    {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn(null);

        $bridge = new LegacyAuthenticationBridge($tokenStorage);

        $this->assertFalse($bridge->isAuthenticated());
    }

    public function testIsAuthenticatedReturnsTrueWhenAuthenticated(): void
    {
        $user = new SecurityUser([
            'id' => 42,
            'email' => 'test@test.com',
            'login' => null,
            'password' => null,
            'role' => 'USER',
            'authority_id' => 1,
            'status' => 1,
            'certificate_hash' => 'hash123',
            'name' => 'Test',
            'givenname' => 'User'
        ]);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        $bridge = new LegacyAuthenticationBridge($tokenStorage);

        $this->assertTrue($bridge->isAuthenticated());
    }
}
