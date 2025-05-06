<?php

namespace S2low\Tests\Factory;

use S2low\Factory\EnvironnementFactory;
use PHPUnit\Framework\TestCase;
use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Lib\SessionWrapper;

class EnvironnementFactoryTest extends TestCase
{
    public function testCreate()
    {
        $sessionWrapperMock = $this->createMock(SessionWrapper::class);
        $convertApiLoginsFromIso = true;

        $factory = new EnvironnementFactory(
            $sessionWrapperMock,
            $convertApiLoginsFromIso
        );

        $environnementCreated = $factory->create();

        self::assertInstanceOf(Environnement::class, $environnementCreated);
    }
}
