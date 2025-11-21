<?php

namespace S2low\Security;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class X509Authenticator extends AbstractAuthenticator
{
    private CertificateExtractor $certificateExtractor;
    private CredentialsExtractor $credentialsExtractor;
    private UserAuthenticationStrategy $authenticationStrategy;
    private LoggerInterface $logger;
    private \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface $tokenStorage;

    public function __construct(
        CertificateExtractor $certificateExtractor,
        CredentialsExtractor $credentialsExtractor,
        UserAuthenticationStrategy $authenticationStrategy,
        LoggerInterface $logger,
        \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface $tokenStorage
    ) {
        $this->certificateExtractor = $certificateExtractor;
        $this->credentialsExtractor = $credentialsExtractor;
        $this->authenticationStrategy = $authenticationStrategy;
        $this->logger = $logger;
        $this->tokenStorage = $tokenStorage;
    }

    public function supports(Request $request): ?bool
    {
        if ($this->hasAuthenticatedUser()) {
            return false;
        }

        if (!$this->certificateExtractor->hasValidCertificate($request)) {
            return false;
        }

        // Ne pas authentifier sur les pages de login par password
        $path = $request->getPathInfo();
        if ($path === '/connexion' || $path === '/connexion/multicompte') {
            $this->logger->info('X509Authenticator: skipping password login page', ['path' => $path]);
            return false;
        }

        if ($this->isLoginPageDisplayRequest($request)) {
            return false;
        }

        if ($this->shouldUseExistingAuthentication($request)) {
            return false;
        }

        return true;
    }

    public function authenticate(Request $request): Passport
    {
        $certificateInfo = $this->extractCertificateOrFail($request);
        $certificateHash = $certificateInfo['certificate_hash'];
        $certificateRgs2 = $certificateInfo['certificate_rgs_2_etoiles'];

        $user = $this->tryAuthenticateByNonce($request, $certificateHash);
        if ($user) {
            return $this->buildPassportForUser($user);
        }

        $user = $this->authenticationStrategy->authenticateByCertificate($certificateHash, $certificateRgs2);
        if ($user) {
            return $this->buildPassportForUser($user);
        }

        return $this->authenticateWithCredentials($request, $certificateHash, $certificateRgs2);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($this->isLoginFormSubmission($request)) {
            return new RedirectResponse('/');
        }

        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $messageKey = $exception->getMessageKey();

        if ($messageKey === 'multiple_accounts') {
            return new RedirectResponse('/login.php');
        }

        return new RedirectResponse('/login.php?error=' . urlencode($messageKey));
    }

    private function isLoginPageDisplayRequest(Request $request): bool
    {
        return $request->getPathInfo() === '/login.php' && $request->isMethod('GET');
    }

    private function isLoginFormSubmission(Request $request): bool
    {
        return $request->getPathInfo() === '/login.php' && $request->isMethod('POST');
    }

    private function shouldUseExistingAuthentication(Request $request): bool
    {
        if (!$this->hasAuthenticatedUser()) {
            return false;
        }

        if ($this->isLoginFormSubmission($request)) {
            return false;
        }

        if ($this->isTestEnvironment()) {
            return false;
        }

        return true;
    }

    private function hasAuthenticatedUser(): bool
    {
        $token = $this->tokenStorage->getToken();
        return $token && $token->getUser() instanceof SecurityUser;
    }

    private function isTestEnvironment(): bool
    {
        return defined('PHPUNIT_COMPOSER_INSTALL')
            || defined('__PHPUNIT_PHAR__')
            || getenv('APP_ENV') === 'test';
    }

    /**
     * @return array{ssl_client_verify: string, subject_dn: string, issuer_dn: string, certificate_hash: string, ssl_client_cert: string, certificate_rgs_2_etoiles: string}
     */
    private function extractCertificateOrFail(Request $request): array
    {
        $certificateInfo = $this->certificateExtractor->extract($request);

        if (!$certificateInfo) {
            throw new CustomUserMessageAuthenticationException('Aucune information de certificat trouvée');
        }

        return $certificateInfo;
    }

    private function tryAuthenticateByNonce(Request $request, string $certificateHash): ?SecurityUser
    {
        $nonceParameters = $this->extractNonceParameters($request);

        if (!$nonceParameters) {
            return null;
        }

        return $this->authenticationStrategy->authenticateByNonce(
            $certificateHash,
            $nonceParameters['nonce'],
            $nonceParameters['login'],
            $nonceParameters['hash']
        );
    }

    /**
     * @return array{nonce: string, login: string, hash: string}|null
     */
    private function extractNonceParameters(Request $request): ?array
    {
        $nonce = $request->query->get('nounce');
        $login = $request->query->get('login');
        $hash = $request->query->get('hash');

        if (!$nonce || !$login || !$hash) {
            return null;
        }

        return ['nonce' => $nonce, 'login' => $login, 'hash' => $hash];
    }

    private function authenticateWithCredentials(
        Request $request,
        string $certificateHash,
        string $certificateRgs2
    ): Passport {
        $credentials = $this->credentialsExtractor->extract($request);

        if (!$this->hasCredentials($credentials)) {
            $this->throwMultipleAccountsException($request, $certificateHash, $certificateRgs2);
        }

        $user = $this->authenticationStrategy->authenticateByCertificateAndCredentials(
            $certificateHash,
            $certificateRgs2,
            $credentials['login'],
            $credentials['password']
        );

        return $this->buildPassportForUser($user);
    }

    /**
     * @param array{login: string|null, password: string|null} $credentials
     */
    private function hasCredentials(array $credentials): bool
    {
        return !empty($credentials['login']) && !empty($credentials['password']);
    }

    private function throwMultipleAccountsException(Request $request, string $certificateHash, string $certificateRgs2): void
    {
        $userCount = $this->authenticationStrategy->countUsersForCertificate($certificateHash, $certificateRgs2);

        $this->logger->info('X509Authenticator: multiple accounts detected, redirecting to login', [
            'certificate_hash' => $certificateHash,
            'user_count' => $userCount,
            'path' => $request->getPathInfo()
        ]);

        throw new CustomUserMessageAuthenticationException('multiple_accounts');
    }

    private function buildPassportForUser(SecurityUser $user): Passport
    {
        return new SelfValidatingPassport(
            new UserBadge($user->getUserIdentifier(), fn() => $user)
        );
    }
}
