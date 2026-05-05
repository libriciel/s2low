<?php

namespace S2low\Security;

use Psr\Log\LoggerInterface;
use S2low\Security\Exceptions\CertificateExtractionException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class SharedCertificateAuthenticator extends AbstractAuthenticator
{
    use TargetPathTrait;
    use CreateSecurityResponseTrait;

    public function __construct(
        private readonly CredentialsExtractor $credentialsExtractor,
        private readonly CertificateExtractor $certificateExtractor,
        private readonly SecurityUserProvider $userProvider,
        private readonly HttpUtils $httpUtils,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        if (!empty($request->query->get('nounce'))) {
            return false;
        }

        if ($request->server->get('PHP_AUTH_USER')) {
            return true;
        }

        if ($this->isTestEnvironment()) {
            return false;
        }

        $IsNotPostRequestFromLogin = !($request->getPathInfo() === '/login.php' && $request->isMethod('POST'));

        if ($IsNotPostRequestFromLogin) {
            return false;
        }

        return true;
    }

    public function authenticate(Request $request): Passport
    {
        try {
            $certificateHash = $this->certificateExtractor->extractCertificateOrFail($request)['certificate_hash'];

            $credentials = $this->credentialsExtractor->extract($request);
            if (empty($credentials['login'])) {
                throw new CustomUserMessageAuthenticationException('empty_login');
            }

            if (empty($credentials['password'])) {
                throw new CustomUserMessageAuthenticationException('empty_password');
            }

            $user = $this->userProvider->loadUserByCertificateAndLogin($certificateHash, $credentials['login']);
        } catch (CertificateExtractionException $e) {
            throw new CustomUserMessageAuthenticationException($e->getMessage());
        }

        if (!$user) {
            throw new CustomUserMessageAuthenticationException('bad_credentials');
        }

        return new Passport(
            new UserBadge($user->getUserIdentifier(), fn() => $user),
            new PasswordCredentials($credentials['password'])
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $targetPath = $this->getTargetPath($request->getSession(), $firewallName);

        if ($targetPath) {
            return new RedirectResponse($targetPath);
        }

        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = $exception->getMessage();
        $loginUrl = '/login.php';

        if (!empty($message)) {
            $loginUrl = '/login.php?error=' . urlencode($message);
        }

        if (str_contains($request->getRequestUri(), '/api')) {
            return $this->createConnexionImpossibleResponse();
        }

        $targetPath = $this->getTargetPath($request->getSession(), 'main');
        if ($targetPath) {
            $this->saveTargetPath($request->getSession(), 'main', $targetPath);
        }

        return new RedirectResponse($loginUrl);
    }

    private function isTestEnvironment(): bool
    {
        return defined('PHPUNIT_COMPOSER_INSTALL')
            || defined('__PHPUNIT_PHAR__')
            || getenv('APP_ENV') === 'test';
    }
}
