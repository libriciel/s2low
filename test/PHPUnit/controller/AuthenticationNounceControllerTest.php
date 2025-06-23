<?php

declare(strict_types=1);

namespace PHPUnit\controller;

use IntegrationTests\S2lowIntegrationTestCase;
use S2low\Enum\UserRole;
use S2lowLegacy\Class\Authentification;
use S2lowLegacy\Controller\AuthenticationNounceController;
use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Lib\ObjectInstancier;
use S2lowLegacy\Lib\SessionWrapper;

/**
 *
 */
class AuthenticationNounceControllerTest extends S2lowIntegrationTestCase
{
    public function testGetNounce(): void
    {
        $server = [
            'SSL_CLIENT_VERIFY' => 'SUCCESS',
            'SSL_CLIENT_S_DN' => 'test_subject',
            'SSL_CLIENT_I_DN' => 'test_issuer_2',
            'TESTING_CERTIFICATE_HASH' => 'T5k4Cv8eWZMDNWo0h/a6DgDLTVw=',
            'PHP_AUTH_USER' => 'login3',
            'PHP_AUTH_PW' => 'password',
        ];

        $environnement = $environnement ?? new Environnement(
            [],
            [],
            [],
            self::getContainer()->get(SessionWrapper::class),
            $server,
            false,
        );

        self::getContainer()->set(Environnement::class, $environnement);

        $authentificationNounceController = self::getContainer()->get(AuthenticationNounceController::class);
        ob_start();
        $authentificationNounceController->getAction();
        $output = ob_get_clean();
        $jsonOutput = json_decode($output, true, 512, JSON_THROW_ON_ERROR);

        static::assertSame(['nounce'], array_keys($jsonOutput));
    }
}
