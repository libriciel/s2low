<?php


class RedirectException extends Exception {}

class FrontController {

	private $objectInstancier;
	
	public function __construct($objectInstancier){
		$this->objectInstancier = $objectInstancier;
	}
	
	public function go($controller, $action){
		$controllerName = "{$controller}Controller";
		$actionName = "{$action}Action";
		$controllerObject = new $controllerName($this->objectInstancier);
		try {
			$controllerObject->_actionBefore($controller,$action);
			$controllerObject->$actionName();
			$controllerObject->_actionAfter();
		} catch (RedirectException $e){
			//nothing to do
		}
	}
	
}