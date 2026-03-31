<?php

namespace Test\PHPUnit\Security;

use PHPUnit\Framework\TestCase;
use S2low\Security\SecurityUser;

class SecurityUserTest extends TestCase
{
    private function createUserData(array $overrides = []): array
    {
        return array_merge([
            'id' => 1,
            'email' => 'test@test.com',
            'login' => 'testuser',
            'password' => 'hashed_password',
            'role' => 'USER',
            'authority_id' => 1,
            'authority_group_id' => null,
            'status' => 1,
            'certificate_hash' => 'hash123',
            'name' => 'Test',
            'givenname' => 'User'
        ], $overrides);
    }

    public function testGetIdReturnsCorrectValue(): void
    {
        $user = new SecurityUser($this->createUserData(['id' => 42]));
        $this->assertEquals(42, $user->getId());
    }

    public function testGetUserIdentifierReturnsIdAsString(): void
    {
        $user = new SecurityUser($this->createUserData(['id' => 42]));
        $this->assertEquals('42', $user->getUserIdentifier());
    }

    public function testGetEmailReturnsCorrectValue(): void
    {
        $user = new SecurityUser($this->createUserData(['email' => 'custom@test.com']));
        $this->assertEquals('custom@test.com', $user->getEmail());
    }

    public function testGetLoginReturnsCorrectValue(): void
    {
        $user = new SecurityUser($this->createUserData(['login' => 'customlogin']));
        $this->assertEquals('customlogin', $user->getLogin());
    }

    public function testGetPasswordReturnsCorrectValue(): void
    {
        $user = new SecurityUser($this->createUserData(['password' => 'secret']));
        $this->assertEquals('secret', $user->getPassword());
    }

    public function testGetRolesForRegularUser(): void
    {
        $user = new SecurityUser($this->createUserData(['role' => 'USER']));
        $roles = $user->getRoles();

        $this->assertCount(1, $roles);
        $this->assertContains('ROLE_USER', $roles);
    }

    public function testGetRolesForAdmin(): void
    {
        $user = new SecurityUser($this->createUserData(['role' => 'ADM']));
        $roles = $user->getRoles();

        $this->assertCount(1, $roles);
        $this->assertContains('ROLE_ADM', $roles);
    }

    public function testGetRolesForGroupAdmin(): void
    {
        $user = new SecurityUser($this->createUserData(['role' => 'GADM']));
        $roles = $user->getRoles();

        $this->assertCount(1, $roles);
        $this->assertContains('ROLE_GADM', $roles);
    }

    public function testGetRolesForSuperAdmin(): void
    {
        $user = new SecurityUser($this->createUserData(['role' => 'SADM']));
        $roles = $user->getRoles();

        $this->assertCount(1, $roles);
        $this->assertContains('ROLE_SADM', $roles);
    }

    public function testGetRolesForArchivist(): void
    {
        $user = new SecurityUser($this->createUserData(['role' => 'ARCH']));
        $roles = $user->getRoles();

        $this->assertCount(1, $roles);
        $this->assertContains('ROLE_ARCH', $roles);
    }

    public function testGetLegacyRoleReturnsOriginalRole(): void
    {
        $user = new SecurityUser($this->createUserData(['role' => 'SADM']));
        $this->assertEquals('SADM', $user->getLegacyRole());
    }

    public function testGetAuthorityIdReturnsCorrectValue(): void
    {
        $user = new SecurityUser($this->createUserData(['authority_id' => 5]));
        $this->assertEquals(5, $user->getAuthorityId());
    }

    public function testGetAuthorityGroupIdReturnsCorrectValue(): void
    {
        $user = new SecurityUser($this->createUserData(['authority_group_id' => 3]));
        $this->assertEquals(3, $user->getAuthorityGroupId());
    }

    public function testGetAuthorityGroupIdReturnsNullWhenNotSet(): void
    {
        $user = new SecurityUser($this->createUserData(['authority_group_id' => null]));
        $this->assertNull($user->getAuthorityGroupId());
    }

    public function testGetStatusReturnsCorrectValue(): void
    {
        $user = new SecurityUser($this->createUserData(['status' => 1]));
        $this->assertEquals(1, $user->getStatus());
    }

    public function testGetCertificateHashReturnsCorrectValue(): void
    {
        $user = new SecurityUser($this->createUserData(['certificate_hash' => 'myhash']));
        $this->assertEquals('myhash', $user->getCertificateHash());
    }

    public function testGetNameReturnsCorrectValue(): void
    {
        $user = new SecurityUser($this->createUserData(['name' => 'Dupont']));
        $this->assertEquals('Dupont', $user->getName());
    }

    public function testGetGivennameReturnsCorrectValue(): void
    {
        $user = new SecurityUser($this->createUserData(['givenname' => 'Jean']));
        $this->assertEquals('Jean', $user->getGivenname());
    }

    public function testIsActiveReturnsTrueWhenStatusIsOne(): void
    {
        $user = new SecurityUser($this->createUserData(['status' => 1]));
        $this->assertTrue($user->isActive());
    }

    public function testIsActiveReturnsFalseWhenStatusIsZero(): void
    {
        $user = new SecurityUser($this->createUserData(['status' => 0]));
        $this->assertFalse($user->isActive());
    }

    public function testEraseCredentialsDoesNotThrowException(): void
    {
        $user = new SecurityUser($this->createUserData());
        $user->eraseCredentials();
        $this->assertTrue(true); // vérifie simplement qu'aucune exception n'est lancée
    }
}
