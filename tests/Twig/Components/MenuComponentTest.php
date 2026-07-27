<?php

declare(strict_types=1);

namespace S2low\Tests\Twig\Components;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use S2low\Security\LegacyAuthenticationBridge;
use S2low\Security\SecurityUser;
use S2low\Services\UserAffiliation;
use S2low\Twig\Components\MenuComponent;
use S2lowLegacy\Model\MessageAdminSQL;
use S2lowLegacy\Model\UserSQL;

class MenuComponentTest extends TestCase
{
    private MessageAdminSQL&MockObject $messageAdminSQL;
    private UserAffiliation&MockObject $userAffiliation;
    private UserSQL&MockObject $userSQL;
    private LegacyAuthenticationBridge&MockObject $authenticationBridge;
    private MenuComponent $menuComponent;

    protected function setUp(): void
    {
        $this->messageAdminSQL = $this->createMock(MessageAdminSQL::class);
        $this->userAffiliation = $this->createMock(UserAffiliation::class);
        $this->userSQL = $this->createMock(UserSQL::class);
        $this->authenticationBridge = $this->createMock(LegacyAuthenticationBridge::class);

        $this->menuComponent = new MenuComponent(
            $this->messageAdminSQL,
            $this->userAffiliation,
            $this->userSQL,
            $this->authenticationBridge
        );
    }

    public function testInitialsAreBuiltFromTheCivility(): void
    {
        $this->authenticationBridge->method('getAuthenticatedUser')
            ->willReturn($this->aUser(['givenname' => 'Jean', 'name' => 'Dupont']));

        static::assertSame('JD', $this->menuComponent->getInitials());
    }

    public function testInitialsAreEmptyWhenNoUser(): void
    {
        $this->authenticationBridge->method('getAuthenticatedUser')->willReturn(null);

        static::assertSame('', $this->menuComponent->getInitials());
    }

    public function testSuperAdminHasNoAffiliation(): void
    {
        $this->authenticationBridge->method('getAuthenticatedUser')
            ->willReturn($this->aUser(['role' => 'SADM']));

        $this->userAffiliation->method('getType')->with('SADM')->willReturn(null);
        $this->userAffiliation->method('getName')->willReturn(null);

        static::assertNull($this->menuComponent->getAffiliationType());
        static::assertNull($this->menuComponent->getAffiliationName());
    }

    public function testGroupAdminIsAffiliatedToItsGroup(): void
    {
        $this->authenticationBridge->method('getAuthenticatedUser')
            ->willReturn($this->aUser(['role' => 'GADM', 'authority_id' => 42, 'authority_group_id' => 7]));

        $this->userAffiliation->method('getType')->with('GADM')->willReturn(UserAffiliation::TYPE_GROUP);
        $this->userAffiliation->method('getName')->with('GADM', 42, 7)->willReturn('Groupement du Limousin');

        static::assertSame(UserAffiliation::TYPE_GROUP, $this->menuComponent->getAffiliationType());
        static::assertSame('Groupement du Limousin', $this->menuComponent->getAffiliationName());
    }

    public function testAffiliationIsResolvedFromTheConnectedUser(): void
    {
        $this->authenticationBridge->method('getAuthenticatedUser')
            ->willReturn($this->aUser(['role' => 'ADM', 'authority_id' => 42, 'authority_group_id' => 7]));

        $this->userAffiliation->method('getName')
            ->with('ADM', 42, 7)
            ->willReturn('Mairie de Saint-Just-le-Martel');
        $this->userAffiliation->method('getType')
            ->with('ADM')
            ->willReturn(UserAffiliation::TYPE_AUTHORITY);

        static::assertSame('Mairie de Saint-Just-le-Martel', $this->menuComponent->getAffiliationName());
        static::assertSame(UserAffiliation::TYPE_AUTHORITY, $this->menuComponent->getAffiliationType());
    }

    public function testAffiliationIsNullWhenNoUser(): void
    {
        $this->authenticationBridge->method('getAuthenticatedUser')->willReturn(null);
        $this->userAffiliation->expects(static::never())->method('getName');
        $this->userAffiliation->expects(static::never())->method('getType');

        static::assertNull($this->menuComponent->getAffiliationName());
        static::assertNull($this->menuComponent->getAffiliationType());
    }

    public function testCertificateIsNotSharedWhenNoUser(): void
    {
        $this->authenticationBridge->method('getAuthenticatedUser')->willReturn(null);

        static::assertFalse($this->menuComponent->isCertificateSharedWithOtherUsers());
    }

    public function testCertificateIsNotSharedWhenUserIsAloneOnIt(): void
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
