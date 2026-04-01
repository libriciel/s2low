<?php

namespace Test\PHPUnit\controller;

use PHPUnit\Framework\TestCase;
use S2low\Controller\SecurityController;
use LogicException;

class SecurityControllerTest extends TestCase
{
    public function testLogoutThrowsLogicException(): void
    {
        $controller = new SecurityController();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('This method can be blank - it will be intercepted by the logout key on your firewall.');

        $controller->logout();
    }
}
