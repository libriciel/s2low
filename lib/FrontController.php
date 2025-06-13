<?php

namespace S2lowLegacy\Lib;

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

        $controllerObject = $this->objectInstancier->get($controllerName);
        $controllerObject->setFiles($_FILES);
        try {
            $controllerObject->_actionBefore($controller, $action);
            $controllerObject->$actionName();
            $controllerObject->_actionAfter();
        } catch (RedirectException $e) {
            //nothing to do
        }
    }
}
