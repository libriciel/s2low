<?php

namespace S2low\Security;

use S2low\Security\Exceptions\CertificateExtractionException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class SimpleCertificateAuthenticator extends AbstractAuthenticator
{
    use TargetPathTrait;
    use CreateSecurityResponseTrait;

    public function __construct(
        private readonly CertificateExtractor $certificateExtractor,
        private readonly SecurityUserProvider $userProvider,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        if ($request->server->get('PHP_AUTH_USER')) {
            return false;
        }

        if (!empty($request->query->get('nounce'))) {
            return false;
        }

        if ($this->isTestEnvironment()) {
            return true;
        }

        if ($this->hasAuthenticatedUser()) {
            return false;
        }

        if ($request->getPathInfo() === '/login.php') {
            return false;
        }

        if ($this->isNounceRequest($request)) {
            return false;
        }

        return true;
    }

    public function authenticate(Request $request): Passport
    {
        try {
            $certificateInfo = $this->certificateExtractor->extractCertificateOrFail($request);
            $certificateHash = $certificateInfo['certificate_hash'];

            $users = $this->userProvider->loadUsersByCertificateHash($certificateHash);
        } catch (CertificateExtractionException $exception) {
            throw new AuthenticationException($exception->getMessage());
        }

        if (count($users) === 0) {
            throw new AuthenticationException('connection_impossible');
        } elseif (count($users) > 1) {
            throw new AuthenticationException('multiple_accounts');
        }

        return new SelfValidatingPassport(
            new UserBadge($users[0]->getUserIdentifier(), fn() => $users[0])
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
        $exceptionMessage = $exception->getMessage();

        if ($exceptionMessage === 'connection_impossible') {
            $request->getSession()->getFlashBag()->add(
                'warning',
                "Le certificat n'est pas valide : aucun compte trouvé. "
                . 'Merci de contacter votre administrateur ou de déposer un ticket '
                . "d'assistance chez votre éditeur ou votre mutualisant."
            );

            return new RedirectResponse($this->urlGenerator->generate('authentication_failed'));
        }

        if ($exceptionMessage === 'multiple_accounts') {
            if ($request->get('api') == '1') {
                return $this->createConnexionImpossibleResponse();
            }

            $loginUrl = $this->urlGenerator->generate('app_legacy_login', ['error' => $exceptionMessage]);

            $targetPath = $request->getRequestUri();
            if ($targetPath) {
                $this->saveTargetPath($request->getSession(), 'main', $targetPath);
            }

            return new RedirectResponse($loginUrl);
        }

        return new RedirectResponse($this->urlGenerator->generate('authentication_failed'));
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
     * @param Request $request
     * @return bool
     */
    private function isNounceRequest(Request $request): bool
    {
        $nonce = $request->query->get('nounce');

        return !empty($nonce);
    }
}
