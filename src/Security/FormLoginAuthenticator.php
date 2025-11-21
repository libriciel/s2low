<?php

namespace S2low\Security;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

/**
 * Authenticator pour l'authentification par login/mot de passe.
 *
 * @see docs/AUTHENTICATION.md Documentation complète du système d'authentification
 */
class FormLoginAuthenticator extends AbstractLoginFormAuthenticator
{
    public function __construct(
        private readonly PasswordUserProvider $userProvider,
        private readonly LoggerInterface $logger
    ) {
    }

    public function supports(Request $request): bool
    {
        $path = $request->getPathInfo();
        $isPost = $request->isMethod('POST');
        $supports = $path === '/connexion' && $isPost;

        $this->logger->info('FormLoginAuthenticator::supports', [
            'path' => $path,
            'method' => $request->getMethod(),
            'supports' => $supports
        ]);

        return $supports;
    }

    public function authenticate(Request $request): Passport
    {
        $login = $request->request->get('login', '');
        $password = $request->request->get('password', '');

        $this->logger->info('Tentative de connexion', [
            'login' => $login,
            'ip' => $request->getClientIp()
        ]);

        // Stocker le login pour le réafficher en cas d'erreur
        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $login);

        return new Passport(
            new UserBadge($login, function ($userIdentifier) {
                return $this->userProvider->loadUserByIdentifier($userIdentifier);
            }),
            new PasswordCredentials($password),
            [
                new RememberMeBadge(), // Permet "Se souvenir de moi" (optionnel)
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): Response
    {
        $user = $token->getUser();

        if ($user instanceof SecurityUser) {
            $this->logger->info('Utilisateur authentifié avec succès via login/Mot de passe', [
                'user_id' => $user->getId(),
                'login' => $user->getLogin(),
                'ip' => $request->getClientIp()
            ]);
        }

        // Rediriger vers la page d'accueil
        return new RedirectResponse('/');
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $login = $request->request->get('login', '');

        $this->logger->warning('L\'authentification n\'a pas aboutie.', [
            'login' => $login,
            'ip' => $request->getClientIp(),
            'reason' => $exception->getMessageKey()
        ]);

        return new RedirectResponse($this->getLoginUrl($request));
    }

    protected function getLoginUrl(Request $request): string
    {
        return '/connexion';
    }
}
