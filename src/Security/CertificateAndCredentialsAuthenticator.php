<?php

namespace S2low\Security;

use S2low\Entity\User;
use S2lowLegacy\Class\HttpsConnexion;
use S2lowLegacy\Model\NounceSQL;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * Authenticator personnalisé pour S2low
 * Gère l'authentification par certificat SSL + login/password + nonce
 */
class CertificateAndCredentialsAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    public function __construct(
        private S2lowUserProvider $userProvider,
        private HttpsConnexion $httpsConnexion,
        private NounceSQL $nounceSQL,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    /**
     * Détermine si cet authenticator doit être utilisé pour cette requête
     */
    public function supports(Request $request): ?bool
    {
        // Supporte les soumissions POST sur /security/login ET les requêtes avec certificat
        return $request->isMethod('POST') && str_starts_with($request->getPathInfo(), '/security/login')
            || $this->hasCertificate($request);
    }

    /**
     * Vérifie si un certificat SSL client est présent
     */
    private function hasCertificate(Request $request): bool
    {
        $certInfo = $this->httpsConnexion->getCertificateInfo();
        return $certInfo && isset($certInfo['certificate_hash']) && !empty($certInfo['certificate_hash']);
    }

    /**
     * Authentifie l'utilisateur
     */
    public function authenticate(Request $request): Passport
    {
        // 1. Vérifier nonce en priorité
        if ($this->httpsConnexion->hasNonceParameters()) {
            return $this->authenticateWithNonce($request);
        }

        // 2. Récupérer infos certificat
        $certInfo = $this->httpsConnexion->getCertificateInfo();
        $hasCertificate = $certInfo && isset($certInfo['certificate_hash']) && !empty($certInfo['certificate_hash']);

        // 3. Récupérer credentials
        $credentials = $this->getCredentials($request);

        // CAS 1: Certificat présent
        if ($hasCertificate) {
            // Si login/password fournis (cas legacy du double user pour un certificat)
            if ($credentials['login'] && $credentials['password']) {
                try {
                    $user = $this->userProvider->loadUserByCertificateHashAndCredentials(
                        $certInfo['certificate_hash'],
                        $credentials['login'],
                        $credentials['password']
                    );

                    return new Passport(
                        new UserBadge($user->getUserIdentifier(), function($userIdentifier) {
                            return $this->userProvider->loadUserByIdentifier($userIdentifier);
                        }),
                        new PasswordCredentials($credentials['password'])
                    );
                } catch (\Exception $e) {
                    throw new AuthenticationException('Login ou mot de passe incorrect');
                }
            }

            // Sinon, authentification par certificat seul
            try {
                $user = $this->userProvider->loadUserByCertificateHashAndCredentials(
                    $certInfo['certificate_hash'],
                    null,
                    null
                );

                // Un seul utilisateur trouvé, authentification par certificat seul
                return new SelfValidatingPassport(
                    new UserBadge($user->getUserIdentifier(), function($userIdentifier) {
                        return $this->userProvider->loadUserByIdentifier($userIdentifier);
                    })
                );
            } catch (\Exception $e) {
                // Plusieurs utilisateurs partagent ce certificat : afficher formulaire
                throw new AuthenticationException($e->getMessage());
            }
        }

        // CAS 2: Pas de certificat => login/password OBLIGATOIRE
        if (!$credentials['login'] || !$credentials['password']) {
            throw new AuthenticationException('Login et mot de passe requis');
        }

        // Authentification par login/password uniquement
        try {
            $user = $this->userProvider->loadUserByIdentifier($credentials['login']);

            return new Passport(
                new UserBadge($user->getUserIdentifier(), function($userIdentifier) {
                    return $this->userProvider->loadUserByIdentifier($userIdentifier);
                }),
                new PasswordCredentials($credentials['password'])
            );
        } catch (\Exception $e) {
            throw new AuthenticationException('Login ou mot de passe incorrect');
        }
    }

    /**
     * Récupère les credentials depuis la requête
     */
    private function getCredentials(Request $request): array
    {
        // POST (formulaire)
        if ($request->isMethod('POST')) {
            return [
                'login' => $request->request->get('login'),
                'password' => $request->request->get('password')
            ];
        }

        // HTTP Basic Auth (depuis Apache)
        $apacheCredentials = $this->httpsConnexion->getCredentialsFromApache();
        return [
            'login' => $apacheCredentials['login'],
            'password' => $apacheCredentials['password']
        ];
    }

    /**
     * Authentification via nonce (lien temporaire)
     */
    private function authenticateWithNonce(Request $request): Passport
    {
        $nonceParams = $this->httpsConnexion->getNonceParameters();

        // Vérifier le nonce
        $authorityId = $this->nounceSQL->verify(...$nonceParams);

        if (!$authorityId || !isset($authorityId['authority_id'])) {
            throw new AuthenticationException('Nonce invalide ou expiré');
        }

        // Récupérer le login depuis les paramètres nonce
        $login = $nonceParams[0];

        // Charger l'utilisateur par son login
        try {
            $user = $this->userProvider->loadUserByIdentifier($login);

            // Vérifier que l'utilisateur appartient à la bonne authority
            if ($user->getAuthorityId() !== (int)$authorityId['authority_id']) {
                throw new AuthenticationException('Nonce ne correspond pas à l\'utilisateur');
            }

            return new SelfValidatingPassport(
                new UserBadge($user->getUserIdentifier(), function($userIdentifier) {
                    return $this->userProvider->loadUserByIdentifier($userIdentifier);
                })
            );
        } catch (\Exception $e) {
            throw new AuthenticationException('Authentification par nonce échouée: ' . $e->getMessage());
        }
    }

    /**
     * Appelé en cas de succès d'authentification
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Vérifier que l'utilisateur est actif
        $user = $token->getUser();
        if ($user instanceof User) {
            if (!$user->getLegacyUser()->isActive()) {
                throw new AuthenticationException('Compte désactivé');
            }
        }

        // Rediriger vers la page demandée ou la page d'accueil
        if ($request->hasSession()) {
            $session = $request->getSession();
            if ($targetPath = $session->get('_security.main.target_path')) {
                return new RedirectResponse($targetPath);
            }
        }

        return new RedirectResponse($this->urlGenerator->generate('app_home'));
    }

    /**
     * Appelé en cas d'échec d'authentification
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // Stocker le message d'erreur en session si disponible
        if ($request->hasSession()) {
            $request->getSession()->set('_security.last_error', $exception);
        }

        // Rediriger vers la page de login
        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }

    /**
     * Point d'entrée : appelé quand l'utilisateur n'est pas authentifié
     * et tente d'accéder à une page protégée
     */
    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        // Rediriger vers la page de login
        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }
}
