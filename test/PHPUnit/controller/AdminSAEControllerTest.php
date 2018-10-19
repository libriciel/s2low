<?php

class AdminSAEControllerTest extends S2lowTestCase {

	public function testEditAction(){
		$this->setSuperAdminAuthentication();
		$adminServiceController = $this->getObjectInstancier()->get(AdminSAEController::class);

		$adminServiceController->_actionBefore("AdminSAE","edit");
		$adminServiceController->editAction();
		$this->expectOutputRegex("#Identifiant de l'entité#");
		$adminServiceController->_actionAfter();
	}

	public function testTestAction(){
        $this->setSuperAdminAuthentication();
        $adminServiceController = $this->getObjectInstancier()->get(AdminSAEController::class);
        $this->getObjectInstancier()->get("Environnement")->get()->set('id',1);
        $this->setExpectedException(RedirectException::class);
        $adminServiceController->testAction();
    }

}