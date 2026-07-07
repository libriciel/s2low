<?php

declare(strict_types=1);

namespace PHPUnit\controller;

use IntegrationTests\S2lowIntegrationTestCase;

class AuthenticationNounceControllerFailTest extends S2lowIntegrationTestCase
{
    public function testGetNounceWithoutAuthFails(): void
    {
        $this->client->request(
            'GET',
            '/api/get-nounce.php'
        );

        $response = $this->client->getResponse();

        static::assertSame(401, $response->getStatusCode());
        static::assertStringContainsString('Www-Authenticate', (string)$response->headers);
    }
}
