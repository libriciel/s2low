<?php

declare(strict_types=1);

namespace S2low\Tests\Security;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use S2low\Security\CertificateExtractor;
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
    private const string NO_ACCOUNT_FLASH = "Le certificat n'est pas valide : aucun compte trouvé. "
        . 'Merci de contacter votre administrateur ou de déposer un ticket '
        . "d'assistance chez votre éditeur ou votre mutualisant.";

    private CertificateExtractor&MockObject $certificateExtractor;
    private SecurityUserProvider&MockObject $userProvider;
    private UrlGeneratorInterface&MockObject $urlGenerator;
    private SimpleCertificateAuthenticator $authenticator;

    protected function setUp(): void
    {
        $this->certificateExtractor = $this->createMock(CertificateExtractor::class);
        $this->userProvider = $this->createMock(SecurityUserProvider::class);
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);

        $this->authenticator = new SimpleCertificateAuthenticator(
            $this->certificateExtractor,
            $this->userProvider,
            $tokenStorage,
            $this->urlGenerator,
        );
    }

    private function requestWithSession(): Request
    {
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));

        return $request;
    }

    public function testSupportsReturnsFalseWhenNounceIsInRequest(): void
    {
        // When `nounce` is present, SimpleCertificateAuthenticator hands over to NounceAuthenticator.
        $request = new Request(['nounce' => '12345']);

        static::assertFalse($this->authenticator->supports($request));
    }

    /**
     * @return iterable<string, array{0: list<SecurityUser>, 1: string}>
     */
    public static function provideAuthenticationFailures(): iterable
    {
        yield 'no matching account' => [[], 'connection_impossible'];
        yield 'several matching accounts' => [
            [
                new SecurityUser(['id' => 1, 'login' => 'user1', 'password' => 'secret']),
                new SecurityUser(['id' => 2, 'login' => 'user2', 'password' => 'secret']),
            ],
            'multiple_accounts',
        ];
    }

    /**
     * @dataProvider provideAuthenticationFailures
     *
     * @var SecurityUser[] $users
     */
    public function testAuthenticateThrowsExpectedException(array $users, string $expectedMessage): void
    {
        $this->certificateExtractor->method('extractCertificateOrFail')
            ->willReturn(['certificate_hash' => 'hash']);
        $this->userProvider->method('loadUsersByCertificateHash')->willReturn($users);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->authenticator->authenticate(new Request());
    }


    public function testOnConnectionImpossibleAddsWarningFlashAndRedirectsToFailurePage(): void
    {
        $this->urlGenerator->method('generate')
            ->with('authentication_failed')
            ->willReturn('/connexion-status/');

        $request = $this->requestWithSession();

        $response = $this->authenticator->onAuthenticationFailure(
            $request,
            new AuthenticationException('connection_impossible'),
        );

        static::assertInstanceOf(RedirectResponse::class, $response);
        static::assertSame('/connexion-status/', $response->getTargetUrl());
        static::assertContains(
            self::NO_ACCOUNT_FLASH,
            $request->getSession()->getFlashBag()->get('warning'),
        );
    }

    public function testOnMultipleAccountsRedirectsToLoginWithErrorAndSavesTargetPath(): void
    {
        $this->urlGenerator->method('generate')
            ->with('app_legacy_login', ['error' => 'multiple_accounts'])
            ->willReturn('/login.php?error=multiple_accounts');

        $request = $this->requestWithSession();
        $request->server->set('REQUEST_URI', '/some/target/path');

        $response = $this->authenticator->onAuthenticationFailure(
            $request,
            new AuthenticationException('multiple_accounts'),
        );

        static::assertInstanceOf(RedirectResponse::class, $response);
        static::assertSame('/login.php?error=multiple_accounts', $response->getTargetUrl());
        static::assertSame(
            '/some/target/path',
            $request->getSession()->get('_security.main.target_path'),
        );
    }

    public function testOnMultipleAccountsReturnsKoResponseForApiRequest(): void
    {
        $request = $this->requestWithSession();
        $request->query->set('api', '1');

        $response = $this->authenticator->onAuthenticationFailure(
            $request,
            new AuthenticationException('multiple_accounts'),
        );

        static::assertStringStartsWith('KO', $response->getContent());
    }
}
