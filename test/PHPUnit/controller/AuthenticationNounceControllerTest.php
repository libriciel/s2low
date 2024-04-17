<?php

declare(strict_types=1);

namespace PHPUnit\controller;

use S2lowLegacy\Controller\AuthenticationNounceController;
use S2lowTestCase;

/**
 *
 */
class AuthenticationNounceControllerTest extends S2lowTestCase
{
    public function testGetNounce(): void
    {
        $this->setServerAdullactCertificate();
        $this->setServerInfo([
            'PHP_AUTH_USER' => 'alice_é',
            'PHP_AUTH_PW' => 'alice'
        ]);
        /** @var AuthenticationNounceController $authentificationNounceController */
        $authentificationNounceController =  $this->getObjectInstancier()->get(AuthenticationNounceController::class);
        ob_start();
        $authentificationNounceController->getAction();
        $output = ob_get_clean();
        $jsonOutput = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
        static::assertSame(['nounce'], array_keys($jsonOutput));
    }

    private function setServerAdullactCertificate(): void
    {
        $this->setServerInfo([
            'SSL_CLIENT_VERIFY' => 'SUCCESS',
            'SSL_CLIENT_S_DN' => 'adullact',
            'SSL_CLIENT_I_DN' => 'adullact',
            'TESTING_CERTIFICATE_HASH' => 'hash_adullact',
        ]);
    }
}
