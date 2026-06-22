<?php

namespace Test\PHPUnit\Security;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use S2low\Security\CertificateExtractor;
use S2low\Security\CredentialsExtractor;
use S2low\Security\Exceptions\CertificateExtractionException;
use S2low\Security\SecurityUser;
use S2low\Security\SecurityUserProvider;
use S2low\Security\SharedCertificateAuthenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\HttpUtils;

class SharedCertificateAuthenticatorTest extends TestCase
{
    private function createDependencies(): array
    {
        return [
            $this->createMock(CredentialsExtractor::class),
            $this->createMock(CertificateExtractor::class),
            $this->createMock(SecurityUserProvider::class),
            $this->createMock(HttpUtils::class),
            $this->createMock(UrlGeneratorInterface::class),
            $this->createMock(LoggerInterface::class),
        ];
    }

    public function testAuthenticateThrowsOnCertificateExtractionError(): void
    {
        [$credExtractor, $certExtractor, $provider, $httpUtils, $urlGen, $logger] = $this->createDependencies();

        $certExtractor->method('extractCertificateOrFail')->willThrowException(new CertificateExtractionException('cert error'));

        $authenticator = new SharedCertificateAuthenticator(
            $credExtractor,
            $certExtractor,
            $provider,
            $httpUtils,
            $urlGen,
            $logger
        );

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('cert error');

        $authenticator->authenticate(new Request());
    }

    public function testAuthenticateThrowsOnEmptyLogin(): void
    {
        [$credExtractor, $certExtractor, $provider, $httpUtils, $urlGen, $logger] = $this->createDependencies();

        $certExtractor->method('extractCertificateOrFail')->willReturn(['certificate_hash' => 'hash']);
        $credExtractor->method('extract')->willReturn(['login' => '', 'password' => 'pwd']);

        $authenticator = new SharedCertificateAuthenticator(
            $credExtractor,
            $certExtractor,
            $provider,
            $httpUtils,
            $urlGen,
            $logger
        );

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('empty_login');

        $authenticator->authenticate(new Request());
    }

    public function testAuthenticateThrowsOnEmptyPassword(): void
    {
        [$credExtractor, $certExtractor, $provider, $httpUtils, $urlGen, $logger] = $this->createDependencies();

        $certExtractor->method('extractCertificateOrFail')->willReturn(['certificate_hash' => 'hash']);
        $credExtractor->method('extract')->willReturn(['login' => 'login', 'password' => '']);

        $authenticator = new SharedCertificateAuthenticator(
            $credExtractor,
            $certExtractor,
            $provider,
            $httpUtils,
            $urlGen,
            $logger
        );

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('empty_password');

        $authenticator->authenticate(new Request());
    }

    public function testAuthenticateThrowsOnBadCredentials(): void
    {
        [$credExtractor, $certExtractor, $provider, $httpUtils, $urlGen, $logger] = $this->createDependencies();

        $certExtractor->method('extractCertificateOrFail')->willReturn(['certificate_hash' => 'hash']);
        $credExtractor->method('extract')->willReturn(['login' => 'login', 'password' => 'pwd']);
        $provider->method('loadUserByCertificateAndLogin')->willReturn(null);

        $authenticator = new SharedCertificateAuthenticator(
            $credExtractor,
            $certExtractor,
            $provider,
            $httpUtils,
            $urlGen,
            $logger
        );

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('bad_credentials');

        $authenticator->authenticate(new Request());
    }

    public function testAuthenticateReturnsPassport(): void
    {
        [$credExtractor, $certExtractor, $provider, $httpUtils, $urlGen, $logger] = $this->createDependencies();

        $certExtractor->method('extractCertificateOrFail')->willReturn(['certificate_hash' => 'hash']);
        $credExtractor->method('extract')->willReturn(['login' => 'login', 'password' => 'pwd']);

        $user = new SecurityUser(['id' => 1, 'login' => 'login', 'role' => 'USER']);
        $provider->method('loadUserByCertificateAndLogin')->willReturn($user);

        $authenticator = new SharedCertificateAuthenticator(
            $credExtractor,
            $certExtractor,
            $provider,
            $httpUtils,
            $urlGen,
            $logger
        );

        $passport = $authenticator->authenticate(new Request());

        $this->assertTrue($passport->hasBadge(UserBadge::class));
        $this->assertTrue($passport->hasBadge(PasswordCredentials::class));
    }

    public function testOnAuthenticationFailureRedirectsToLogin(): void
    {
        [$credExtractor, $certExtractor, $provider, $httpUtils, $urlGen, $logger] = $this->createDependencies();

        $authenticator = new SharedCertificateAuthenticator(
            $credExtractor,
            $certExtractor,
            $provider,
            $httpUtils,
            $urlGen,
            $logger
        );

        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $request->server->set('REQUEST_URI', '/target');

        $response = $authenticator->onAuthenticationFailure($request, new AuthenticationException('my_error'));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/login.php?error=my_error', $response->getTargetUrl());
    }

    public function testOnAuthenticationSuccessRedirectsToTargetPath(): void
    {
        [$credExtractor, $certExtractor, $provider, $httpUtils, $urlGen, $logger] = $this->createDependencies();

        $authenticator = new SharedCertificateAuthenticator(
            $credExtractor,
            $certExtractor,
            $provider,
            $httpUtils,
            $urlGen,
            $logger
        );

        $request = new Request();
        $session = new Session(new MockArraySessionStorage());
        $session->set('_security.main.target_path', '/custom_path');
        $request->setSession($session);

        $tokenMock = $this->createMock(TokenInterface::class);

        $response = $authenticator->onAuthenticationSuccess($request, $tokenMock, 'main');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/custom_path', $response->getTargetUrl());
    }
}
