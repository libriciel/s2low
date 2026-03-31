<?php

namespace Test\PHPUnit\Security;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use S2low\Security\CertificateExtractor;
use S2low\Security\CredentialsExtractor;
use S2low\Security\SecurityUser;
use S2low\Security\SecurityUserProvider;
use S2low\Security\SimpleCertificateAuthenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class SimpleCertificateAuthenticatorTest extends TestCase
{
    private function createDependencies(): array
    {
        return [
            $this->createMock(CertificateExtractor::class),
            $this->createMock(CredentialsExtractor::class),
            $this->createMock(SecurityUserProvider::class),
            $this->createMock(UrlGeneratorInterface::class),
            $this->createMock(TokenStorageInterface::class),
            $this->createMock(LoggerInterface::class),
        ];
    }

    public function testSupportsReturnsFalseWhenNounceIsInRequest(): void
    {
        [$certExtractor, $credExtractor, $provider, $urlGen, $tokenStorage, $logger] = $this->createDependencies();

        $authenticator = new SimpleCertificateAuthenticator(
            $certExtractor,
            $credExtractor,
            $provider,
            $urlGen,
            $tokenStorage,
            $logger
        );

        $request = new Request(['nounce' => '12345']);

        // Le bug potentiel a été identifié : si `nounce` est incomplet, le SimpleAuthenticator l'ignorait et rien n'était authentifié.
        // Désormais le SimpleAuthenticator passe la main à NounceAuthenticator qui lui se chargera de lever "Paramètres manquants."
        $this->assertFalse($authenticator->supports($request));
    }

    public function testAuthenticateThrowsExceptionIfNoUserFound(): void
    {
        [$certExtractor, $credExtractor, $provider, $urlGen, $tokenStorage, $logger] = $this->createDependencies();

        $certExtractor->method('extractCertificateOrFail')->willReturn(['certificate_hash' => 'hash']);
        $provider->method('loadUsersByCertificateHash')->willReturn([]);

        $authenticator = new SimpleCertificateAuthenticator(
            $certExtractor,
            $credExtractor,
            $provider,
            $urlGen,
            $tokenStorage,
            $logger
        );

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('connection_impossible');

        $authenticator->authenticate(new Request());
    }

    public function testAuthenticateThrowsExceptionIfMultipleUsersFound(): void
    {
        [$certExtractor, $credExtractor, $provider, $urlGen, $tokenStorage, $logger] = $this->createDependencies();

        $certExtractor->method('extractCertificateOrFail')->willReturn(['certificate_hash' => 'hash']);

        $user1 = new SecurityUser(['id' => 1]);
        $user2 = new SecurityUser(['id' => 2]);
        $provider->method('loadUsersByCertificateHash')->willReturn([$user1, $user2]);

        $authenticator = new SimpleCertificateAuthenticator(
            $certExtractor,
            $credExtractor,
            $provider,
            $urlGen,
            $tokenStorage,
            $logger
        );

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('multiple_accounts');

        $authenticator->authenticate(new Request());
    }

    public function testOnAuthenticationFailureRedirectsToLoginOnMultipleAccounts(): void
    {
        [$certExtractor, $credExtractor, $provider, $urlGen, $tokenStorage, $logger] = $this->createDependencies();

        $authenticator = new SimpleCertificateAuthenticator(
            $certExtractor,
            $credExtractor,
            $provider,
            $urlGen,
            $tokenStorage,
            $logger
        );

        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $request->server->set('REQUEST_URI', '/some/target/path');

        $response = $authenticator->onAuthenticationFailure($request, new AuthenticationException('multiple_accounts'));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/login.php?error=multiple_accounts', $response->getTargetUrl());
        $this->assertEquals('/some/target/path', $request->getSession()->get('_security.main.target_path'));
    }
}
