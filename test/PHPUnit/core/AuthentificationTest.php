<?php

use PHPUnit\Framework\TestCase;
use S2low\Security\LegacyAuthenticationBridge;
use S2low\Security\SecurityUser;
use S2lowLegacy\Class\Authentification;
use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Lib\SessionWrapper;

class AuthentificationTest extends TestCase
{
    private function createEnvironnementMock(): Environnement
    {
        $session = $this->createMock(SessionWrapper::class);
        $session->method('set')->willReturn(null);

        $environnement = $this->createMock(Environnement::class);
        $environnement->method('session')->willReturn($session);

        return $environnement;
    }

    public function testAuthenticateReturnsUserIdWhenAuthenticated(): void
    {
        $environnement = $this->createEnvironnementMock();

        $authBridge = $this->createMock(LegacyAuthenticationBridge::class);
        $authBridge->method('isAuthenticated')->willReturn(true);
        $authBridge->method('getAuthenticatedUserId')->willReturn(42);

        $authentification = new Authentification($environnement, $authBridge);

        $this->assertEquals(42, $authentification->authenticate());
    }

    public function testAuthenticateThrowsExceptionWhenNotAuthenticated(): void
    {
        $environnement = $this->createEnvironnementMock();

        $authBridge = $this->createMock(LegacyAuthenticationBridge::class);
        $authBridge->method('isAuthenticated')->willReturn(false);

        $authentification = new Authentification($environnement, $authBridge);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("La connexion n'a pas pu être établie");

        $authentification->authenticate();
    }

    public function testAuthenticateThrowsExceptionWhenNoBridge(): void
    {
        $environnement = $this->createEnvironnementMock();

        $authentification = new Authentification($environnement, null);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("La connexion n'a pas pu être établie");

        $authentification->authenticate();
    }

    public function testAuthenticateSynchronizesSessionWithLegacy(): void
    {
        $session = $this->createMock(SessionWrapper::class);
        $session->expects($this->once())
            ->method('set')
            ->with('id_login', 42);

        $environnement = $this->createMock(Environnement::class);
        $environnement->method('session')->willReturn($session);

        $authBridge = $this->createMock(LegacyAuthenticationBridge::class);
        $authBridge->method('isAuthenticated')->willReturn(true);
        $authBridge->method('getAuthenticatedUserId')->willReturn(42);

        $authentification = new Authentification($environnement, $authBridge);

        $authentification->authenticate();
    }
}
