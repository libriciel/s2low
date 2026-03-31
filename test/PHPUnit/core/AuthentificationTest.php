<?php

use S2low\Security\CredentialsExtractor;
use S2low\Security\LegacyAuthenticationBridge;
use S2lowLegacy\Class\Authentification;
use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Lib\SessionWrapper;
use Symfony\Component\HttpFoundation\Request;

class AuthentificationTest extends S2lowTestCase
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

    public function testAPIRequestWithHTTPLogin(): void
    {
        $loginInISO = mb_convert_encoding('alice_é', 'ISO-8859-1', 'UTF-8');
        $request = new Request(server: ['PHP_AUTH_USER' => $loginInISO, 'PHP_AUTH_PW' => 'alice']);

        static::assertSame(
            ['login' => 'alice_é', 'password' => 'alice'],
            $this->getContainer()->get(CredentialsExtractor::class)->extract($request)
        );
    }
}
