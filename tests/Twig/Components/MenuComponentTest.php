<?php

declare(strict_types=1);

namespace S2low\Tests\Twig\Components;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use S2low\Security\LegacyAuthenticationBridge;
use S2low\Security\SecurityUser;
use S2low\Twig\Components\MenuComponent;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\MessageAdminSQL;
use S2lowLegacy\Model\UserSQL;

class MenuComponentTest extends TestCase
{
    private MessageAdminSQL&MockObject $messageAdminSQL;
    private AuthoritySQL&MockObject $authoritySQL;
    private UserSQL&MockObject $userSQL;
    private LegacyAuthenticationBridge&MockObject $authenticationBridge;
    private MenuComponent $menuComponent;

    protected function setUp(): void
    {
        $this->messageAdminSQL = $this->createMock(MessageAdminSQL::class);
        $this->authoritySQL = $this->createMock(AuthoritySQL::class);
        $this->userSQL = $this->createMock(UserSQL::class);
        $this->authenticationBridge = $this->createMock(LegacyAuthenticationBridge::class);

        $this->menuComponent = new MenuComponent(
            $this->messageAdminSQL,
            $this->authoritySQL,
            $this->userSQL,
            $this->authenticationBridge
        );
    }

    public function testAuthorityNameIsNullWhenNoUser(): void
    {
        $this->authenticationBridge->method('getAuthenticatedUser')->willReturn(null);

        static::assertNull($this->menuComponent->getAuthorityNameForAuthorityAdmin());
    }

    public function testAuthorityNameIsShownForAuthorityAdmin(): void
    {
        $this->authenticationBridge->method('getAuthenticatedUser')
            ->willReturn($this->aUser(['role' => 'ADM', 'authority_id' => 42]));

        $this->authoritySQL->method('getInfo')
            ->with(42)
            ->willReturn(['id' => 42, 'name' => 'Ma Mairie Test']);

        static::assertSame('Ma Mairie Test', $this->menuComponent->getAuthorityNameForAuthorityAdmin());
    }

    public function testAuthorityNameIsHiddenForGroupAdmin(): void
    {
        $this->authenticationBridge->method('getAuthenticatedUser')
            ->willReturn($this->aUser(['role' => 'GADM', 'authority_group_id' => 7]));

        static::assertNull($this->menuComponent->getAuthorityNameForAuthorityAdmin());
    }

    public function testCertificateIsNotSharedWhenNoUser(): void
    {
        $this->authenticationBridge->method('getAuthenticatedUser')->willReturn(null);

        static::assertFalse($this->menuComponent->isCertificateSharedWithOtherUsers());
    }

    public function testCertificateIsNotSharedWhenUserIsAloneOnTheCertificate(): void
    {
        $this->authenticationBridge->method('getAuthenticatedUser')
            ->willReturn($this->aUser(['certificate_hash' => 'abc123']));

        $this->userSQL->method('getNbUserWithMyCertificate')
            ->with('abc123')
            ->willReturn(1);

        static::assertFalse($this->menuComponent->isCertificateSharedWithOtherUsers());
    }

    public function testCertificateIsSharedWhenSeveralUsersHaveIt(): void
    {
        $this->authenticationBridge->method('getAuthenticatedUser')
            ->willReturn($this->aUser(['certificate_hash' => 'abc123']));

        $this->userSQL->method('getNbUserWithMyCertificate')
            ->with('abc123')
            ->willReturn(3);

        static::assertTrue($this->menuComponent->isCertificateSharedWithOtherUsers());
    }

    /**
     * @param array<string, mixed> $userData
     */
    private function aUser(array $userData): SecurityUser
    {
        return new SecurityUser($userData + [
            'id' => 1,
            'name' => 'Dupont',
            'givenname' => 'Jean',
            'role' => 'USER',
            'authority_id' => 42,
            'login' => null,
            'password' => null,
        ]);
    }
}
