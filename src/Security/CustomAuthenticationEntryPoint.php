<?php

namespace S2low\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * Entry point qui redirige vers la page de login appropriée selon le contexte
 *
 * @see docs/AUTHENTICATION.md Documentation complète du système d'authentification
 */
class CustomAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    public function __construct(
        private readonly CertificateExtractor $certificateExtractor
    ) {
    }

    public function start(Request $request, ?AuthenticationException $authException = null): RedirectResponse
    {
        // Si l'utilisateur a un certificat valide, rediriger vers /login.php
        // (page avec sélection de compte si multiple)
        if ($this->certificateExtractor->hasValidCertificate($request)) {
            return new RedirectResponse('/login.php');
        }

        // Se connecte normalement
        return new RedirectResponse('/connexion');
    }
}
