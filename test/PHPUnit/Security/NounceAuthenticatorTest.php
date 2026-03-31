<?php

namespace Test\PHPUnit\Security;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use S2low\Security\CertificateExtractor;
use S2low\Security\NounceAuthenticator;
use S2low\Security\SecurityUser;
use S2low\Security\SecurityUserProvider;
use S2lowLegacy\Model\NounceSQL;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

class NounceAuthenticatorTest extends TestCase
{
    private function createDependencies(): array
    {
        return [
            $this->createMock(CertificateExtractor::class),
            $this->createMock(NounceSQL::class),
            $this->createMock(SecurityUserProvider::class),
            $this->createMock(LoggerInterface::class),
        ];
    }

    public function testSupportsReturnsFalseIfNoCertificate(): void
    {
        [$extractor, $nounceSQL, $provider, $logger] = $this->createDependencies();

        $request = new Request();
        $extractor->method('hasValidCertificate')->willReturn(false);

        $authenticator = new NounceAuthenticator($extractor, $nounceSQL, $provider, $logger);

        $this->assertFalse($authenticator->supports($request));
    }

    public function testSupportsReturnsFalseIfBasicAuth(): void
    {
        [$extractor, $nounceSQL, $provider, $logger] = $this->createDependencies();

        $request = new Request();
        $request->server->set('PHP_AUTH_USER', 'alice');
        $extractor->method('hasValidCertificate')->willReturn(true);

        $authenticator = new NounceAuthenticator($extractor, $nounceSQL, $provider, $logger);

        $this->assertFalse($authenticator->supports($request));
    }

    public function testSupportsReturnsTrueIfNounceParamExists(): void
    {
        [$extractor, $nounceSQL, $provider, $logger] = $this->createDependencies();

        // Ceci met en évidence le correctif où seul le paramètre nounce déclenche supports
        $request = new Request(
            ['nounce' => '12345'],
            [],
            [],
            [],
            [],
            ['REQUEST_URI' => '/modules/actes/actes_transac_post_confirm_api.php']
        );

        $extractor->method('hasValidCertificate')->willReturn(true);

        $authenticator = new NounceAuthenticator($extractor, $nounceSQL, $provider, $logger);

        $this->assertTrue($authenticator->supports($request));
    }

    public function testAuthenticateThrowsExceptionIfMissingParameters(): void
    {
        [$extractor, $nounceSQL, $provider, $logger] = $this->createDependencies();

        // le paramètre nounce est présent mais il manque hash et login
        $request = new Request(['nounce' => '12345']);

        $authenticator = new NounceAuthenticator($extractor, $nounceSQL, $provider, $logger);

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Paramètres manquants.');

        $authenticator->authenticate($request);
    }

    public function testAuthenticateThrowsExceptionIfNounceInvalid(): void
    {
        [$extractor, $nounceSQL, $provider, $logger] = $this->createDependencies();

        $request = new Request(['nounce' => '12345', 'login' => 'alice', 'hash' => 'abc']);
        $nounceSQL->method('verify')->willReturn(false);

        $authenticator = new NounceAuthenticator($extractor, $nounceSQL, $provider, $logger);

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Informations incorrect.');

        $authenticator->authenticate($request);
    }

    public function testAuthenticateReturnsPassportOnSuccess(): void
    {
        [$extractor, $nounceSQL, $provider, $logger] = $this->createDependencies();

        $request = new Request(['nounce' => '12345', 'login' => 'alice', 'hash' => 'abc']);

        $nounceSQL->expects($this->once())->method('verify')->with('alice', '12345', 'abc')->willReturn(42);

        $extractor->method('extractCertificateOrFail')->willReturn(['certificate_hash' => 'cert_hash']);

        $user = new SecurityUser(['id' => 123, 'login' => 'alice', 'authority_id' => 42]);
        $provider->method('loadUserByCertificateAndAuthority')->with('cert_hash', 42)->willReturn($user);

        $authenticator = new NounceAuthenticator($extractor, $nounceSQL, $provider, $logger);

        $passport = $authenticator->authenticate($request);

        $this->assertEquals('123', $passport->getUser()->getUserIdentifier());
    }

    public function testAuthenticateLogsAndThrowsOnUserNotFound(): void
    {
        [$extractor, $nounceSQL, $provider, $logger] = $this->createDependencies();

        $request = new Request(['nounce' => '12345', 'login' => 'alice', 'hash' => 'abc']);
        $nounceSQL->method('verify')->willReturn(42);
        $extractor->method('extractCertificateOrFail')->willReturn(['certificate_hash' => 'cert_hash']);

        $provider->method('loadUserByCertificateAndAuthority')->willThrowException(new UserNotFoundException('Not found'));

        $logger->expects($this->once())->method('error')->with('Not found');

        $authenticator = new NounceAuthenticator($extractor, $nounceSQL, $provider, $logger);

        $this->expectException(CustomUserMessageAuthenticationException::class);

        $authenticator->authenticate($request);
    }
}
