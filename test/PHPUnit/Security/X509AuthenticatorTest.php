<?php

namespace Test\PHPUnit\Security;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use S2low\Security\SecurityUser;
use S2low\Security\SecurityUserProvider;
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
        ?SecurityUserProvider $userProvider = null,
        ?PasswordHandler $passwordHandler = null,
        ?X509Certificate $x509Certificate = null
    ): X509Authenticator {
        $userProvider = $userProvider ?? $this->createMock(SecurityUserProvider::class);
        $passwordHandler = $passwordHandler ?? $this->createMock(PasswordHandler::class);
        $x509Certificate = $x509Certificate ?? $this->createMock(X509Certificate::class);
        $nounceSQL = $this->createMock(NounceSQL::class);
        $logger = $this->createMock(LoggerInterface::class);
        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        return new X509Authenticator(
            $userProvider,
            $passwordHandler,
            $x509Certificate,
            $nounceSQL,
            $logger,
            $tokenStorage
        );
    }

    public function testSupportsReturnsFalseWithoutCertificate(): void
    {
        $authenticator = $this->createAuthenticator();
        $request = Request::create('/test');

        $this->assertFalse($authenticator->supports($request));
    }

    public function testSupportsReturnsTrueWithValidCertificate(): void
    {
        $authenticator = $this->createAuthenticator();
        $request = Request::create('/test');
        $request->server->set('SSL_CLIENT_VERIFY', 'SUCCESS');

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn(null);

        // Recréer avec le mock
        $authenticator = new X509Authenticator(
            $this->createMock(SecurityUserProvider::class),
            $this->createMock(PasswordHandler::class),
            $this->createMock(X509Certificate::class),
            $this->createMock(NounceSQL::class),
            $this->createMock(LoggerInterface::class),
            $tokenStorage
        );

        $this->assertTrue($authenticator->supports($request));
    }

    public function testSupportsReturnsFalseForLoginPageGet(): void
    {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn(null);

        $authenticator = new X509Authenticator(
            $this->createMock(SecurityUserProvider::class),
            $this->createMock(PasswordHandler::class),
            $this->createMock(X509Certificate::class),
            $this->createMock(NounceSQL::class),
            $this->createMock(LoggerInterface::class),
            $tokenStorage
        );

        $request = Request::create('/login.php', 'GET');
        $request->server->set('SSL_CLIENT_VERIFY', 'SUCCESS');

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

        $userProvider = $this->createMock(SecurityUserProvider::class);
        $userProvider->method('loadUsersByCertificateHashAndRgs2')
            ->willReturn([$user]);

        $x509Certificate = $this->createMock(X509Certificate::class);
        $x509Certificate->method('getInfo')->willReturn([
            'subject_name' => 'test',
            'issuer_name' => 'test',
            'certificate_hash' => 'hash123'
        ]);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        $authenticator = new X509Authenticator(
            $userProvider,
            $this->createMock(PasswordHandler::class),
            $x509Certificate,
            $this->createMock(NounceSQL::class),
            $this->createMock(LoggerInterface::class),
            $tokenStorage
        );

        $request = Request::create('/test');
        $request->server->set('SSL_CLIENT_VERIFY', 'SUCCESS');
        $request->server->set('SSL_CLIENT_CERT', 'cert_content');

        $passport = $authenticator->authenticate($request);
        $this->assertNotNull($passport);
    }

    public function testAuthenticateWithMultipleUsersNoCredentials(): void
    {
        $user1 = new SecurityUser([
            'id' => 1, 'email' => 'test1@test.com', 'login' => 'user1',
            'password' => 'hash', 'role' => 'USER', 'authority_id' => 1,
            'status' => 1, 'certificate_hash' => 'hash123',
            'name' => 'Test1', 'givenname' => 'User'
        ]);
        $user2 = new SecurityUser([
            'id' => 2, 'email' => 'test2@test.com', 'login' => 'user2',
            'password' => 'hash', 'role' => 'USER', 'authority_id' => 1,
            'status' => 1, 'certificate_hash' => 'hash123',
            'name' => 'Test2', 'givenname' => 'User'
        ]);

        $userProvider = $this->createMock(SecurityUserProvider::class);
        $userProvider->method('loadUsersByCertificateHashAndRgs2')
            ->willReturn([$user1, $user2]);

        $x509Certificate = $this->createMock(X509Certificate::class);
        $x509Certificate->method('getInfo')->willReturn([
            'subject_name' => 'test',
            'issuer_name' => 'test',
            'certificate_hash' => 'hash123'
        ]);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        $authenticator = new X509Authenticator(
            $userProvider,
            $this->createMock(PasswordHandler::class),
            $x509Certificate,
            $this->createMock(NounceSQL::class),
            $this->createMock(LoggerInterface::class),
            $tokenStorage
        );

        $request = Request::create('/test');
        $request->server->set('SSL_CLIENT_VERIFY', 'SUCCESS');
        $request->server->set('SSL_CLIENT_CERT', 'cert_content');

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
        $user2 = new SecurityUser([
            'id' => 2, 'email' => 'test2@test.com', 'login' => 'user2',
            'password' => 'hashed_password', 'role' => 'USER', 'authority_id' => 1,
            'status' => 1, 'certificate_hash' => 'hash123',
            'name' => 'Test2', 'givenname' => 'User'
        ]);

        $userProvider = $this->createMock(SecurityUserProvider::class);
        $userProvider->method('loadUsersByCertificateHashAndRgs2')
            ->willReturn([$user1, $user2]);
        $userProvider->method('loadUserByCertificateAndLogin')
            ->willReturn([$user1]);

        $passwordHandler = $this->createMock(PasswordHandler::class);
        $passwordHandler->method('passwordMatchesHash')
            ->willReturn(true);

        $x509Certificate = $this->createMock(X509Certificate::class);
        $x509Certificate->method('getInfo')->willReturn([
            'subject_name' => 'test',
            'issuer_name' => 'test',
            'certificate_hash' => 'hash123'
        ]);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        $authenticator = new X509Authenticator(
            $userProvider,
            $passwordHandler,
            $x509Certificate,
            $this->createMock(NounceSQL::class),
            $this->createMock(LoggerInterface::class),
            $tokenStorage
        );

        // Simuler un POST avec login/password
        $request = Request::create('/login.php', 'POST', [
            'login' => 'user1',
            'password' => 'password123'
        ]);
        $request->server->set('SSL_CLIENT_VERIFY', 'SUCCESS');
        $request->server->set('SSL_CLIENT_CERT', 'cert_content');

        $passport = $authenticator->authenticate($request);
        $this->assertNotNull($passport);
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
        $userProvider = $this->createMock(SecurityUserProvider::class);
        $userProvider->method('loadUsersByCertificateHashAndRgs2')
            ->willReturn([]);

        $x509Certificate = $this->createMock(X509Certificate::class);
        $x509Certificate->method('getInfo')->willReturn([
            'subject_name' => 'test',
            'issuer_name' => 'test',
            'certificate_hash' => 'hash123'
        ]);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        $authenticator = new X509Authenticator(
            $userProvider,
            $this->createMock(PasswordHandler::class),
            $x509Certificate,
            $this->createMock(NounceSQL::class),
            $this->createMock(LoggerInterface::class),
            $tokenStorage
        );

        $request = Request::create('/test');
        $request->server->set('SSL_CLIENT_VERIFY', 'SUCCESS');
        $request->server->set('SSL_CLIENT_CERT', 'cert_content');

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage("Le certificat n'est pas valide : aucun compte trouvé");

        $authenticator->authenticate($request);
    }

    public function testAuthenticateThrowsExceptionForIncorrectLogin(): void
    {
        $user1 = new SecurityUser([
            'id' => 1, 'email' => 'test1@test.com', 'login' => 'user1',
            'password' => 'hash', 'role' => 'USER', 'authority_id' => 1,
            'status' => 1, 'certificate_hash' => 'hash123',
            'name' => 'Test1', 'givenname' => 'User'
        ]);
        $user2 = new SecurityUser([
            'id' => 2, 'email' => 'test2@test.com', 'login' => 'user2',
            'password' => 'hash', 'role' => 'USER', 'authority_id' => 1,
            'status' => 1, 'certificate_hash' => 'hash123',
            'name' => 'Test2', 'givenname' => 'User'
        ]);

        $userProvider = $this->createMock(SecurityUserProvider::class);
        $userProvider->method('loadUsersByCertificateHashAndRgs2')
            ->willReturn([$user1, $user2]);
        $userProvider->method('loadUserByCertificateAndLogin')
            ->willReturn([]);

        $x509Certificate = $this->createMock(X509Certificate::class);
        $x509Certificate->method('getInfo')->willReturn([
            'subject_name' => 'test',
            'issuer_name' => 'test',
            'certificate_hash' => 'hash123'
        ]);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        $authenticator = new X509Authenticator(
            $userProvider,
            $this->createMock(PasswordHandler::class),
            $x509Certificate,
            $this->createMock(NounceSQL::class),
            $this->createMock(LoggerInterface::class),
            $tokenStorage
        );

        $request = Request::create('/login.php', 'POST', [
            'login' => 'wronguser',
            'password' => 'password123'
        ]);
        $request->server->set('SSL_CLIENT_VERIFY', 'SUCCESS');
        $request->server->set('SSL_CLIENT_CERT', 'cert_content');

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('login_incorrect');

        $authenticator->authenticate($request);
    }

    public function testAuthenticateThrowsExceptionForIncorrectPassword(): void
    {
        $user1 = new SecurityUser([
            'id' => 1, 'email' => 'test1@test.com', 'login' => 'user1',
            'password' => 'hashed_password', 'role' => 'USER', 'authority_id' => 1,
            'status' => 1, 'certificate_hash' => 'hash123',
            'name' => 'Test1', 'givenname' => 'User'
        ]);
        $user2 = new SecurityUser([
            'id' => 2, 'email' => 'test2@test.com', 'login' => 'user2',
            'password' => 'hashed_password', 'role' => 'USER', 'authority_id' => 1,
            'status' => 1, 'certificate_hash' => 'hash123',
            'name' => 'Test2', 'givenname' => 'User'
        ]);

        $userProvider = $this->createMock(SecurityUserProvider::class);
        $userProvider->method('loadUsersByCertificateHashAndRgs2')
            ->willReturn([$user1, $user2]);
        $userProvider->method('loadUserByCertificateAndLogin')
            ->willReturn([$user1]);

        $passwordHandler = $this->createMock(PasswordHandler::class);
        $passwordHandler->method('passwordMatchesHash')
            ->willReturn(false);

        $x509Certificate = $this->createMock(X509Certificate::class);
        $x509Certificate->method('getInfo')->willReturn([
            'subject_name' => 'test',
            'issuer_name' => 'test',
            'certificate_hash' => 'hash123'
        ]);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        $authenticator = new X509Authenticator(
            $userProvider,
            $passwordHandler,
            $x509Certificate,
            $this->createMock(NounceSQL::class),
            $this->createMock(LoggerInterface::class),
            $tokenStorage
        );

        $request = Request::create('/login.php', 'POST', [
            'login' => 'user1',
            'password' => 'wrongpassword'
        ]);
        $request->server->set('SSL_CLIENT_VERIFY', 'SUCCESS');
        $request->server->set('SSL_CLIENT_CERT', 'cert_content');

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('password_incorrect');

        $authenticator->authenticate($request);
    }
}
