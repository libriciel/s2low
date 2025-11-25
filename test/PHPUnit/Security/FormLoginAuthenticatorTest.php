<?php

namespace Test\PHPUnit\Security;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use S2low\Security\FormLoginAuthenticator;
use S2low\Security\PasswordUserProvider;
use S2low\Security\SecurityUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * Tests pour FormLoginAuthenticator
 *
 * @see docs/AUTHENTICATION.md Documentation complète du système d'authentification
 */
class FormLoginAuthenticatorTest extends TestCase
{
    private function createAuthenticator(
        ?PasswordUserProvider $userProvider = null,
        ?LoggerInterface $logger = null
    ): FormLoginAuthenticator {
        $userProvider = $userProvider ?? $this->createMock(PasswordUserProvider::class);
        $logger = $logger ?? $this->createMock(LoggerInterface::class);

        return new FormLoginAuthenticator($userProvider, $logger);
    }

    private function createUser(): SecurityUser
    {
        return new SecurityUser([
            'id' => 1,
            'email' => 'test@test.com',
            'login' => 'testuser',
            'password' => '$2y$13$hashed_password',
            'role' => 'USER',
            'authority_id' => 1,
            'status' => 1,
            'certificate_hash' => null,
            'name' => 'Test',
            'givenname' => 'User'
        ]);
    }

    public function testSupportsReturnsTrueForConnexionPost(): void
    {
        $authenticator = $this->createAuthenticator();
        $request = Request::create('/connexion', 'POST');

        $this->assertTrue($authenticator->supports($request));
    }

    public function testSupportsReturnsFalseForConnexionGet(): void
    {
        $authenticator = $this->createAuthenticator();
        $request = Request::create('/connexion', 'GET');

        $this->assertFalse($authenticator->supports($request));
    }

    public function testSupportsReturnsFalseForOtherRoutes(): void
    {
        $authenticator = $this->createAuthenticator();
        $request = Request::create('/login.php', 'POST');

        $this->assertFalse($authenticator->supports($request));
    }

    public function testSupportsReturnsFalseForOtherPostRoutes(): void
    {
        $authenticator = $this->createAuthenticator();
        $request = Request::create('/some-other-page', 'POST');

        $this->assertFalse($authenticator->supports($request));
    }

    public function testAuthenticateCreatesPassportWithCredentials(): void
    {
        $user = $this->createUser();

        $userProvider = $this->createMock(PasswordUserProvider::class);
        $userProvider->expects($this->once())
            ->method('loadUserByIdentifier')
            ->with('testuser')
            ->willReturn($user);

        $authenticator = $this->createAuthenticator(userProvider: $userProvider);

        $session = new Session(new MockArraySessionStorage());
        $request = Request::create('/connexion', 'POST', [
            'login' => 'testuser',
            'password' => 'testpassword'
        ]);
        $request->setSession($session);

        $passport = $authenticator->authenticate($request);

        $this->assertInstanceOf(\Symfony\Component\Security\Http\Authenticator\Passport\Passport::class, $passport);
        $this->assertEquals('1', $passport->getUser()->getUserIdentifier());
    }

    public function testAuthenticateStoresLastUsernameInSession(): void
    {
        $user = $this->createUser();

        $userProvider = $this->createMock(PasswordUserProvider::class);
        $userProvider->method('loadUserByIdentifier')->willReturn($user);

        $authenticator = $this->createAuthenticator(userProvider: $userProvider);

        $session = new Session(new MockArraySessionStorage());
        $request = Request::create('/connexion', 'POST', [
            'login' => 'testuser',
            'password' => 'testpassword'
        ]);
        $request->setSession($session);

        $authenticator->authenticate($request);

        $this->assertEquals('testuser', $session->get('_security.last_username'));
    }

    public function testOnAuthenticationSuccessRedirectsToHome(): void
    {
        $authenticator = $this->createAuthenticator();
        $request = Request::create('/connexion', 'POST');
        $token = $this->createMock(TokenInterface::class);

        $response = $authenticator->onAuthenticationSuccess($request, $token, 'main');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/', $response->getTargetUrl());
    }

    public function testOnAuthenticationFailureRedirectsToConnexion(): void
    {
        $authenticator = $this->createAuthenticator();
        $request = Request::create('/connexion', 'POST');
        $exception = new AuthenticationException('Invalid credentials');

        $response = $authenticator->onAuthenticationFailure($request, $exception);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/connexion', $response->getTargetUrl());
    }

    public function testGetLoginUrlReturnsConnexion(): void
    {
        $authenticator = $this->createAuthenticator();
        $request = Request::create('/test');

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($authenticator);
        $method = $reflection->getMethod('getLoginUrl');
        $method->setAccessible(true);

        $loginUrl = $method->invoke($authenticator, $request);

        $this->assertEquals('/connexion', $loginUrl);
    }
}
