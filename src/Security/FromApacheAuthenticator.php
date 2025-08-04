<?php

namespace S2low\Security;

use S2lowLegacy\Model\UserSQL;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class FromApacheAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly SSLUserProvider $userProvider,
        private readonly UserSQL $userSql,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function supports(Request $request): ?bool
    {
        return $request->server->get('SSL_CLIENT_VERIFY') === 'SUCCESS';
    }

    /**
     * @inheritDoc
     */
    public function authenticate(Request $request): Passport
    {
        $pem = (string) $request->server->get('SSL_CLIENT_CERT', '');
        $raw = openssl_x509_fingerprint($pem, 'sha1', /*raw_output*/ true);
        $certHash = base64_encode($raw);

        $userId = $this->userSql->getIdsFromConnexionInfo($certHash,'')[0];

        return new SelfValidatingPassport(
            new UserBadge($userId, fn($id) => $this->userProvider->loadUserByIdentifier($id))
        );
    }

    /**
     * @inheritDoc
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // si 2 certificats :
        return new RedirectResponse("/login.php");
        // sinon redirect erreur, certificat invalide / pas de certificat
    }
}
