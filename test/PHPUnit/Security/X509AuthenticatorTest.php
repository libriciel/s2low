<?php

namespace Test\PHPUnit\Security;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use S2low\Security\CertificateExtractor;
use S2low\Security\CredentialsExtractor;
use S2low\Security\SecurityUser;
use S2low\Security\SecurityUserProvider;
use S2low\Security\UserAuthenticationStrategy;
use S2low\Security\X509Authenticator;
use S2lowLegacy\Class\PasswordHandler;
use S2lowLegacy\Lib\X509Certificate;
use S2lowLegacy\Model\NounceSQL;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

class X509AuthenticatorTest extends TestCase
{
    private function createAuthenticator(
        ?CertificateExtractor $certificateExtractor = null,
        ?CredentialsExtractor $credentialsExtractor = null,
        ?UserAuthenticationStrategy $authenticationStrategy = null,
        ?TokenStorageInterface $tokenStorage = null
    ): X509Authenticator {
        $certificateExtractor = $certificateExtractor ?? $this->createMock(CertificateExtractor::class);
        $credentialsExtractor = $credentialsExtractor ?? $this->createMock(CredentialsExtractor::class);
        $authenticationStrategy = $authenticationStrategy ?? $this->createMock(UserAuthenticationStrategy::class);
        $logger = $this->createMock(LoggerInterface::class);
        $tokenStorage = $tokenStorage ?? $this->createMock(TokenStorageInterface::class);

        return new X509Authenticator(
            $certificateExtractor,
            $credentialsExtractor,
            $authenticationStrategy,
            $logger,
            $tokenStorage
        );
    }

    public function testSupportsReturnsFalseWithoutCertificate(): void
    {
        $certificateExtractor = $this->createMock(CertificateExtractor::class);
        $certificateExtractor->method('hasValidCertificate')->willReturn(false);

        $authenticator = $this->createAuthenticator(certificateExtractor: $certificateExtractor);
        $request = Request::create('/test');

        $this->assertFalse($authenticator->supports($request));
    }

    public function testSupportsReturnsTrueWithValidCertificate(): void
    {
        $certificateExtractor = $this->createMock(CertificateExtractor::class);
        $certificateExtractor->method('hasValidCertificate')->willReturn(true);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn(null);

        $authenticator = $this->createAuthenticator(
            certificateExtractor: $certificateExtractor,
            tokenStorage: $tokenStorage
        );

        $request = Request::create('/test');
        $this->assertTrue($authenticator->supports($request));
    }

    public function testSupportsReturnsFalseForLoginPageGet(): void
    {
        $certificateExtractor = $this->createMock(CertificateExtractor::class);
        $certificateExtractor->method('hasValidCertificate')->willReturn(true);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn(null);

        $authenticator = $this->createAuthenticator(
            certificateExtractor: $certificateExtractor,
            tokenStorage: $tokenStorage
        );

        $request = Request::create('/login.php', 'GET');

        $this->assertFalse($authenticator->supports($request));
    }

    public function testAuthenticateWithSingleUser(): void
    {
        $user = new SecurityUser([
            'id' => 1,
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

        $certificateExtractor = $this->createMock(CertificateExtractor::class);
        $certificateExtractor->method('extract')->willReturn([
            'ssl_client_verify' => 'SUCCESS',
            'subject_dn' => 'test',
            'issuer_dn' => 'test',
            'certificate_hash' => 'hash123',
            'ssl_client_cert' => 'cert_content',
            'certificate_rgs_2_etoiles' => ''
        ]);

        $authenticationStrategy = $this->createMock(UserAuthenticationStrategy::class);
        $authenticationStrategy->method('authenticateByCertificate')
            ->willReturn($user);

        $authenticator = $this->createAuthenticator(
            certificateExtractor: $certificateExtractor,
            authenticationStrategy: $authenticationStrategy
        );

        $request = Request::create('/test');

        $passport = $authenticator->authenticate($request);
        $this->assertInstanceOf(\Symfony\Component\Security\Http\Authenticator\Passport\Passport::class, $passport);
        $this->assertEquals('1', $passport->getUser()->getUserIdentifier());
    }

    public function testAuthenticateWithMultipleUsersNoCredentials(): void
    {
        $certificateExtractor = $this->createMock(CertificateExtractor::class);
        $certificateExtractor->method('extract')->willReturn([
            'ssl_client_verify' => 'SUCCESS',
            'subject_dn' => 'test',
            'issuer_dn' => 'test',
            'certificate_hash' => 'hash123',
            'ssl_client_cert' => 'cert_content',
            'certificate_rgs_2_etoiles' => ''
        ]);

        $credentialsExtractor = $this->createMock(CredentialsExtractor::class);
        $credentialsExtractor->method('extract')->willReturn([
            'login' => null,
            'password' => null
        ]);

        $authenticationStrategy = $this->createMock(UserAuthenticationStrategy::class);
        $authenticationStrategy->method('authenticateByCertificate')
            ->willReturn(null); // Multiple users
        $authenticationStrategy->method('countUsersForCertificate')
            ->willReturn(2);

        $authenticator = $this->createAuthenticator(
            certificateExtractor: $certificateExtractor,
            credentialsExtractor: $credentialsExtractor,
            authenticationStrategy: $authenticationStrategy
        );

        $request = Request::create('/test');

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('multiple_accounts');

        $authenticator->authenticate($request);
    }

    public function testAuthenticateWithMultipleUsersAndPostCredentials(): void
    {
        $user1 = new SecurityUser([
            'id' => 1, 'email' => 'test1@test.com', 'login' => 'user1',
            'password' => 'hashed_password', 'role' => 'USER', 'authority_id' => 1,
            'status' => 1, 'certificate_hash' => 'hash123',
            'name' => 'Test1', 'givenname' => 'User'
        ]);

        $certificateExtractor = $this->createMock(CertificateExtractor::class);
        $certificateExtractor->method('extract')->willReturn([
            'ssl_client_verify' => 'SUCCESS',
            'subject_dn' => 'test',
            'issuer_dn' => 'test',
            'certificate_hash' => 'hash123',
            'ssl_client_cert' => 'cert_content',
            'certificate_rgs_2_etoiles' => ''
        ]);

        $credentialsExtractor = $this->createMock(CredentialsExtractor::class);
        $credentialsExtractor->method('extract')->willReturn([
            'login' => 'user1',
            'password' => 'password123'
        ]);

        $authenticationStrategy = $this->createMock(UserAuthenticationStrategy::class);
        $authenticationStrategy->method('authenticateByCertificate')
            ->willReturn(null); // Multiple users
        $authenticationStrategy->method('authenticateByCertificateAndCredentials')
            ->willReturn($user1);

        $authenticator = $this->createAuthenticator(
            certificateExtractor: $certificateExtractor,
            credentialsExtractor: $credentialsExtractor,
            authenticationStrategy: $authenticationStrategy
        );

        $request = Request::create('/login.php', 'POST');

        $passport = $authenticator->authenticate($request);
        $this->assertInstanceOf(\Symfony\Component\Security\Http\Authenticator\Passport\Passport::class, $passport);
        $this->assertEquals('1', $passport->getUser()->getUserIdentifier());
    }

    public function testOnAuthenticationSuccessRedirectsToHomeOnLoginPost(): void
    {
        $authenticator = $this->createAuthenticator();
        $request = Request::create('/login.php', 'POST');

        $token = $this->createMock(TokenInterface::class);

        $response = $authenticator->onAuthenticationSuccess($request, $token, 'main');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/', $response->getTargetUrl());
    }

    public function testOnAuthenticationSuccessReturnsNullForOtherPages(): void
    {
        $authenticator = $this->createAuthenticator();
        $request = Request::create('/some-page', 'GET');

        $token = $this->createMock(TokenInterface::class);

        $response = $authenticator->onAuthenticationSuccess($request, $token, 'main');

        $this->assertNull($response);
    }

    public function testOnAuthenticationFailureRedirectsToLoginForMultipleAccounts(): void
    {
        $authenticator = $this->createAuthenticator();
        $request = Request::create('/test');

        $exception = new CustomUserMessageAuthenticationException('multiple_accounts');

        $response = $authenticator->onAuthenticationFailure($request, $exception);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/login.php', $response->getTargetUrl());
    }

    public function testOnAuthenticationFailureRedirectsWithErrorForOtherErrors(): void
    {
        $authenticator = $this->createAuthenticator();
        $request = Request::create('/test');

        $exception = new CustomUserMessageAuthenticationException('password_incorrect');

        $response = $authenticator->onAuthenticationFailure($request, $exception);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString('/login.php?error=password_incorrect', $response->getTargetUrl());
    }

    public function testAuthenticateThrowsExceptionWhenNoUsersFound(): void
    {
        $certificateExtractor = $this->createMock(CertificateExtractor::class);
        $certificateExtractor->method('extract')->willReturn([
            'ssl_client_verify' => 'SUCCESS',
            'subject_dn' => 'test',
            'issuer_dn' => 'test',
            'certificate_hash' => 'hash123',
            'ssl_client_cert' => 'cert_content',
            'certificate_rgs_2_etoiles' => ''
        ]);

        $authenticationStrategy = $this->createMock(UserAuthenticationStrategy::class);
        $authenticationStrategy->method('authenticateByCertificate')
            ->willThrowException(new CustomUserMessageAuthenticationException("Le certificat n'est pas valide : aucun compte trouvé"));

        $authenticator = $this->createAuthenticator(
            certificateExtractor: $certificateExtractor,
            authenticationStrategy: $authenticationStrategy
        );

        $request = Request::create('/test');

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage("Le certificat n'est pas valide : aucun compte trouvé");

        $authenticator->authenticate($request);
    }

    public function testAuthenticateThrowsExceptionForIncorrectLogin(): void
    {
        $certificateExtractor = $this->createMock(CertificateExtractor::class);
        $certificateExtractor->method('extract')->willReturn([
            'ssl_client_verify' => 'SUCCESS',
            'subject_dn' => 'test',
            'issuer_dn' => 'test',
            'certificate_hash' => 'hash123',
            'ssl_client_cert' => 'cert_content',
            'certificate_rgs_2_etoiles' => ''
        ]);

        $credentialsExtractor = $this->createMock(CredentialsExtractor::class);
        $credentialsExtractor->method('extract')->willReturn([
            'login' => 'wronguser',
            'password' => 'password123'
        ]);

        $authenticationStrategy = $this->createMock(UserAuthenticationStrategy::class);
        $authenticationStrategy->method('authenticateByCertificate')
            ->willReturn(null); // Multiple users
        $authenticationStrategy->method('authenticateByCertificateAndCredentials')
            ->willThrowException(new CustomUserMessageAuthenticationException('login_incorrect'));

        $authenticator = $this->createAuthenticator(
            certificateExtractor: $certificateExtractor,
            credentialsExtractor: $credentialsExtractor,
            authenticationStrategy: $authenticationStrategy
        );

        $request = Request::create('/login.php', 'POST');

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('login_incorrect');

        $authenticator->authenticate($request);
    }

    public function testAuthenticateThrowsExceptionForIncorrectPassword(): void
    {
        $certificateExtractor = $this->createMock(CertificateExtractor::class);
        $certificateExtractor->method('extract')->willReturn([
            'ssl_client_verify' => 'SUCCESS',
            'subject_dn' => 'test',
            'issuer_dn' => 'test',
            'certificate_hash' => 'hash123',
            'ssl_client_cert' => 'cert_content',
            'certificate_rgs_2_etoiles' => ''
        ]);

        $credentialsExtractor = $this->createMock(CredentialsExtractor::class);
        $credentialsExtractor->method('extract')->willReturn([
            'login' => 'user1',
            'password' => 'wrongpassword'
        ]);

        $authenticationStrategy = $this->createMock(UserAuthenticationStrategy::class);
        $authenticationStrategy->method('authenticateByCertificate')
            ->willReturn(null); // Multiple users
        $authenticationStrategy->method('authenticateByCertificateAndCredentials')
            ->willThrowException(new CustomUserMessageAuthenticationException('password_incorrect'));

        $authenticator = $this->createAuthenticator(
            certificateExtractor: $certificateExtractor,
            credentialsExtractor: $credentialsExtractor,
            authenticationStrategy: $authenticationStrategy
        );

        $request = Request::create('/login.php', 'POST');

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('password_incorrect');

        $authenticator->authenticate($request);
    }
}
