<?php

namespace S2low\Security;

use Psr\Log\LoggerInterface;
use S2lowLegacy\Class\PasswordHandler;
use S2lowLegacy\Lib\X509Certificate;
use S2lowLegacy\Model\NounceSQL;
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
    private SecurityUserProvider $userProvider;
    private PasswordHandler $passwordHandler;
    private X509Certificate $x509Certificate;
    private NounceSQL $nounceSQL;
    private LoggerInterface $logger;
    private \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface $tokenStorage;

    public function __construct(
        SecurityUserProvider $userProvider,
        PasswordHandler $passwordHandler,
        X509Certificate $x509Certificate,
        NounceSQL $nounceSQL,
        LoggerInterface $logger,
        \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface $tokenStorage
    ) {
        $this->userProvider = $userProvider;
        $this->passwordHandler = $passwordHandler;
        $this->x509Certificate = $x509Certificate;
        $this->nounceSQL = $nounceSQL;
        $this->logger = $logger;
        $this->tokenStorage = $tokenStorage;
    }

    public function supports(Request $request): ?bool
    {
        // Support toutes les requêtes HTTPS avec un certificat client
        if ($request->server->get('SSL_CLIENT_VERIFY') !== 'SUCCESS') {
            return false;
        }

        $path = $request->getPathInfo();

        // Ne pas intercepter la page de login en GET - elle affiche juste le formulaire
        if ($path === '/login.php' && $request->isMethod('GET')) {
            return false;
        }

        // Si l'utilisateur est déjà authentifié en session, ne pas ré-authentifier
        // sauf si c'est un POST vers login.php (re-connexion)
        $token = $this->tokenStorage->getToken();
        if ($token && $token->getUser() instanceof SecurityUser) {
            if ($path === '/login.php' && $request->isMethod('POST')) {
                // Permettre la re-connexion
                return true;
            }
            // Vérifier si on est dans un contexte de test PHPUnit
            // Dans ce cas, le token peut être présent dans le TokenStorage mais pas persisté en session
            // donc on doit quand même authentifier pour que le code legacy fonctionne
            if (defined('PHPUNIT_COMPOSER_INSTALL') || defined('__PHPUNIT_PHAR__') || getenv('APP_ENV') === 'test') {
                return true;
            }
            // Déjà authentifié, pas besoin de ré-authentifier
            return false;
        }

        return true;
    }

    public function authenticate(Request $request): Passport
    {
        $certificateInfo = $this->extractCertificateInfo($request);

        if (!$certificateInfo) {
            throw new CustomUserMessageAuthenticationException('Aucune information de certificat trouvée');
        }

        $certificateHash = $certificateInfo['certificate_hash'];
        $certificateRgs2 = $certificateInfo['certificate_rgs_2_etoiles'] ?? '';

        // Vérifier d'abord si c'est une authentification par nonce
        $nonce = $request->query->get('nounce');
        $nonceLogin = $request->query->get('login');
        $nonceHash = $request->query->get('hash');

        if ($nonce && $nonceLogin && $nonceHash) {
            $authorityId = $this->nounceSQL->verify($nonceLogin, $nonce, $nonceHash);
            if ($authorityId) {
                $user = $this->userProvider->loadUserByCertificateAndAuthority($certificateHash, $authorityId);
                if ($user) {
                    return new SelfValidatingPassport(
                        new UserBadge($user->getUserIdentifier(), fn() => $user)
                    );
                }
            }
        }

        // Récupérer les utilisateurs correspondant au certificat
        $users = $this->userProvider->loadUsersByCertificateHashAndRgs2($certificateHash, $certificateRgs2);

        if (empty($users)) {
            throw new CustomUserMessageAuthenticationException("Le certificat n'est pas valide : aucun compte trouvé");
        }

        // Si un seul utilisateur, authentification directe
        if (count($users) === 1) {
            $user = $users[0];
            return new SelfValidatingPassport(
                new UserBadge($user->getUserIdentifier(), fn() => $user)
            );
        }

        // Plusieurs utilisateurs : nécessite login/password
        // Récupérer depuis HTTP Basic Auth ou depuis le formulaire POST
        $login = $request->server->get('PHP_AUTH_USER');
        $password = $request->server->get('PHP_AUTH_PW');

        // Si pas de HTTP Basic Auth, essayer les données POST
        if (!$login || !$password) {
            $login = $request->request->get('login');
            $password = $request->request->get('password');
        }
        if (!$login || !$password) {
            // Rediriger vers la page de login pour saisir les credentials
            $this->logger->info('X509Authenticator: multiple accounts detected, redirecting to login', [
                'certificate_hash' => $certificateHash,
                'user_count' => count($users),
                'path' => $request->getPathInfo()
            ]);
            throw new CustomUserMessageAuthenticationException('multiple_accounts');
        }

        // Rechercher l'utilisateur avec le bon login
        $matchingUsers = $this->userProvider->loadUserByCertificateAndLogin(
            $certificateHash,
            $certificateRgs2,
            $login
        );

        if (empty($matchingUsers)) {
            throw new CustomUserMessageAuthenticationException('login_incorrect');
        }

        // Vérifier le mot de passe
        foreach ($matchingUsers as $user) {
            if ($this->passwordHandler->passwordMatchesHash($password, $user->getPassword() ?? '', $user->getId())) {
                return new SelfValidatingPassport(
                    new UserBadge($user->getUserIdentifier(), fn() => $user)
                );
            }
        }

        throw new CustomUserMessageAuthenticationException('password_incorrect');
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Si c'est une soumission de formulaire de login, rediriger vers l'accueil
        $path = $request->getPathInfo();
        if ($path === '/login.php' && $request->isMethod('POST')) {
            return new RedirectResponse('/');
        }

        // Sinon continuer vers la page demandée
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $messageKey = $exception->getMessageKey();

        // Pour multiple_accounts, rediriger vers la page de login
        if ($messageKey === 'multiple_accounts') {
            return new RedirectResponse('/login.php');
        }

        // Pour les autres erreurs (login_incorrect, password_incorrect),
        // rediriger vers login avec l'erreur
        return new RedirectResponse('/login.php?error=' . urlencode($messageKey));
    }

    private function extractCertificateInfo(Request $request): ?array
    {
        $sslClientVerify = $request->server->get('SSL_CLIENT_VERIFY');
        if ($sslClientVerify !== 'SUCCESS') {
            return null;
        }

        $sslClientCert = $request->server->get('SSL_CLIENT_CERT');
        if (!$sslClientCert) {
            return null;
        }

        $info = $this->x509Certificate->getInfo($sslClientCert);
        if (!$info) {
            return null;
        }

        $result = [
            'ssl_client_verify' => $sslClientVerify,
            'subject_dn' => $info['subject_name'],
            'issuer_dn' => $info['issuer_name'],
            'certificate_hash' => $info['certificate_hash'],
            'ssl_client_cert' => $sslClientCert,
        ];

        // Gestion du certificat RGS**
        $rgs2Header = $request->headers->get('org-s2low-forward-x509-identification');
        if ($rgs2Header) {
            $result['certificate_rgs_2_etoiles'] = X509Certificate::der2pem(base64_decode($rgs2Header));
        } else {
            $result['certificate_rgs_2_etoiles'] = '';
        }

        return $result;
    }
}
