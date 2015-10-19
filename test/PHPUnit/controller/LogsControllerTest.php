<?php

class LogsControllerTest extends S2lowTestCase {

	public function testViewAction(){
		$_SERVER["QUERY_STRING"] = "";
		$this->setSuperAdminAuthentication();
		$logsController = new LogsController($this->getObjectInstancier());
		$logsController->_actionBefore("Logs","view");
		$logsController->viewAction();
		$this->expectOutputRegex("#Tedetis : Journal d'évènements#");
		$logsController->_actionAfter();
	}

	public function testTitleAdminGroup(){
		$this->setAdminGroupAuthentication();
		$logsController = new LogsController($this->getObjectInstancier());
		$logsController->viewAction();
		$h1_title_expected = "Journal d'évènements du groupe «&nbsp;Groupe de test&nbsp;»";
		$this->assertEquals($h1_title_expected,$logsController->getViewParameter('h1_title'));
	}

	public function testTitleAdminCol(){
		$this->setAdminCol2Authentication();
		$logsController = new LogsController($this->getObjectInstancier());
		$logsController->viewAction();
		$h1_title_expected = "Journal d'évènements de la collectivité «&nbsp;Saint-Andre de Corcy&nbsp;»";
		$this->assertEquals($h1_title_expected,$logsController->getViewParameter('h1_title'));
	}
}