<?php

namespace S2low\Tests\Factory;

use S2low\Factory\SessionWrapperFactory;
use PHPUnit\Framework\TestCase;
use S2lowLegacy\Lib\SessionWrapper;

class SessionWrapperFactoryTest extends TestCase
{
    public function testCreateWithSession(): void
    {
        $_SESSION = [];
        $_SESSION['name'] = 'Session de test';

        $factory = new SessionWrapperFactory();

        $sessionWrapper = $factory->create();

        self::assertInstanceOf(SessionWrapper::class, $sessionWrapper);
    }

    public function testCreateWithoutSession(): void
    {
        $_SESSION = null;
        $factory = new SessionWrapperFactory();

        $sessionWrapper = $factory->create();

        self::assertInstanceOf(SessionWrapper::class, $sessionWrapper);
    }
}
