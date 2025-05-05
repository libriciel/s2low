<?php

use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Lib\FrontController;
use S2lowLegacy\Lib\ObjectInstancier;
use S2lowLegacy\Lib\SessionWrapper;

class FrontControllerTest extends PHPUnit_Framework_TestCase
{
    public function testGo()
    {
        $objectInstancier = new ObjectInstancier();
        $get = array();
        $post = array();
        $request = array();
        $session = array();
        $server = array();
        $sessionWrapper = new SessionWrapper($session);
        $environnement = new Environnement(
            $get,
            $post,
            $request,
            $sessionWrapper,
            $server,
            false
        );
        
        $objectInstancier->set(Environnement::class, $environnement);
        $frontController = new FrontController($objectInstancier);
        require_once(__DIR__ . "/fixtures/MockController.class.php");
        $this->expectOutputString("<h1>Mock Mock Template</h1>");
        $frontController->go("Mock", "mock");
    }
}
