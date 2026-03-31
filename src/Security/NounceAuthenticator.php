<?php

namespace S2low\Security;

use Psr\Log\LoggerInterface;
use S2low\Security\Exceptions\CertificateExtractionException;
use S2lowLegacy\Model\NounceSQL;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class NounceAuthenticator extends AbstractAuthenticator
{
    use TargetPathTrait;
    use CreateSecurityResponseTrait;

    public function __construct(
        private readonly CertificateExtractor $certificateExtractor,
        private readonly NounceSQL $nounceSQL,
        private readonly SecurityUserProvider $securityUserProvider,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        if (!$this->certificateExtractor->hasValidCertificate($request)) {
            return false;
        }

        if ($request->server->get('PHP_AUTH_USER')) {
            return false;
        }

        return true;
    }

    public function authenticate(Request $request): Passport
    {
        $nonceParameters = $this->extractNonceParameters($request);

        if (!$nonceParameters) {
            throw new CustomUserMessageAuthenticationException('Paramètres manquants.');
        }

        $authorityId = $this->nounceSQL->verify(
            $nonceParameters['login'],
            $nonceParameters['nonce'],
            $nonceParameters['hash']
        );

        if (!$authorityId) {
            throw new CustomUserMessageAuthenticationException('Informations incorrect.');
        }

        try {
            $certificateHash = $this->certificateExtractor->extractCertificateOrFail($request)['certificate_hash'];
            $user = $this->securityUserProvider->loadUserByCertificateAndAuthority($certificateHash, $authorityId);
        } catch (CertificateExtractionException | UserNotFoundException $e) {
            $this->logger->error($e->getMessage());
            throw new CustomUserMessageAuthenticationException('Informations incorrect.');
        }
        return new SelfValidatingPassport(
            new UserBadge($user->getUserIdentifier(), fn() => $user)
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $askedToRedirect = $request->get('url_return');
        if (!empty($askedToRedirect)) {
            $askedToRedirectWithErrorMsg = $this->setErrorMessageIfPossible($askedToRedirect);
            return new RedirectResponse($askedToRedirectWithErrorMsg);
        }

        return $this->createConnexionImpossibleResponse();
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

    private function urlIsNotAbleToUseNonce(string $getPathInfo): bool
    {
        $urlAbletoAuthWithNonce = [
            '/modules/actes/actes_transac_post_confirm_api.php',
            '/modules/actes/actes_transac_post_confirm_api_multi.php'
        ];

        $matches = array_filter($urlAbletoAuthWithNonce, function ($url) use ($getPathInfo) {
            return str_contains($getPathInfo, $url);
        });

        return empty($matches);
    }

    private function setErrorMessageIfPossible(string $askedToRedirect): string
    {
        $errorMsg = 'La connexion à échoué';
        $urlWithErrorMsg = str_replace('%%ERROR%%', 1, $askedToRedirect);

        return str_replace('%%MESSAGE%%', $errorMsg, $urlWithErrorMsg);
    }
}
