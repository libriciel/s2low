<?php

declare(strict_types=1);

namespace PHPUnit\lib;

use PHPUnit\Framework\TestCase;
use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Lib\FrontController;
use S2lowLegacy\Lib\ObjectInstancier;

class FrontControllerTest extends TestCase
{
    public function testGo()
    {
        $objectInstancier = new ObjectInstancier();
        $get = array();
        $post = array();
        $request = array();
        $session = array();
        $server = array();
        $objectInstancier->set(Environnement::class, new Environnement($get, $post, $request, $session, $server));
        $frontController = new FrontController($objectInstancier);
        require_once(__DIR__ . "/fixtures/MockController.class.php");
        $this->expectOutputString("<h1>Mock Mock Template</h1>");
        $frontController->go("Mock", "mock");
    }
}
