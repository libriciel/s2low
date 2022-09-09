<?php

namespace S2lowLegacy\Lib;

use S2lowLegacy\Controller\Controller;

class FrontController
{
    private $objectInstancier;

    public function __construct(ObjectInstancier $objectInstancier)
    {
        $this->objectInstancier = $objectInstancier;
    }

    public function go($controller, $action)
    {

        $controllerName = "S2lowLegacy\Controller\\{$controller}Controller";
        $actionName = "{$action}Action";
        /** @var Controller $controllerObject */
        $controllerObject = new $controllerName($this->objectInstancier);
        try {
            $controllerObject->_actionBefore($controller, $action);
            $controllerObject->$actionName();
            $controllerObject->_actionAfter();
        } catch (RedirectException $e) {
            //nothing to do
        }
    }
}
