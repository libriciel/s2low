<?php

namespace S2low\Security;

use Symfony\Component\HttpFoundation\Request;

/**
 * Extraction des credentials utilisateur (login/password).
 *
 * @see docs/AUTHENTICATION.md Documentation complète du système d'authentification
 */
class CredentialsExtractor
{
    /**
     * @return array{login: string|null, password: string|null}
     */
    public function extract(Request $request): array
    {
        $httpBasicAuthCredentials = $this->extractFromHttpBasicAuth($request);

        if ($this->areCredentialsPresent($httpBasicAuthCredentials)) {
            return $httpBasicAuthCredentials;
        }

        return $this->extractFromPostData($request);
    }

    /**
     * @return array{login: string|null, password: string|null}
     */
    private function extractFromHttpBasicAuth(Request $request): array
    {
        return [
            'login' => $request->server->get('PHP_AUTH_USER'),
            'password' => $request->server->get('PHP_AUTH_PW'),
        ];
    }

    /**
     * @return array{login: string|null, password: string|null}
     */
    private function extractFromPostData(Request $request): array
    {
        return [
            'login' => $request->request->get('login'),
            'password' => $request->request->get('password'),
        ];
    }

    /**
     * @param array{login: string|null, password: string|null} $credentials
     */
    private function areCredentialsPresent(array $credentials): bool
    {
        return !empty($credentials['login']) && !empty($credentials['password']);
    }
}
