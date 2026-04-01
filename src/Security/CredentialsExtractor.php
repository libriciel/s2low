<?php

namespace S2low\Security;

use Symfony\Component\HttpFoundation\Request;

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
        $rawLogin = $request->server->get('PHP_AUTH_USER') ?? "";
        $rawPassword = $request->server->get('PHP_AUTH_PW') ?? "";

        return [
            'login' => $this->getStrInUtf8($rawLogin),
            'password' => $this->getStrInUtf8($rawPassword),
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

    private function getStrInUtf8(string $rawString): string
    {
        if (!mb_check_encoding($rawString, 'UTF-8')) {
            $loginInUtf8 = mb_convert_encoding($rawString, 'UTF-8', 'ISO-8859-1');
        } else {
            $loginInUtf8 = $rawString;
        }

        return $loginInUtf8;
    }
}
