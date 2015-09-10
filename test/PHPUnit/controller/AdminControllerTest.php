<?php

require_once(__DIR__."/../init.php");

class AdminControllerTest extends S2lowTestCase {
	
	public function testActionBefore(){
		$this->setSuperAdminAuthentication();
		$adminController = new AdminController($this->getObjectInstancier());		
		$adminController->_actionBefore("Mock", "mock");
	}
	
	public function testAuthoritySiretAction(){
		$this->setSuperAdminAuthentication();
		$adminController = new AdminController($this->getObjectInstancier());
		$_GET['id'] = 1;
		$adminController->authoritySiretAction();
		$this->assertEquals(1, $adminController->authority_info['id']);	
	}

	public function testAuthoritySiretTemplate(){
		$this->setSuperAdminAuthentication();
		$adminController = new AdminController($this->getObjectInstancier());
		$_GET['id'] = 1;
		$adminController->_actionBefore("Admin","authoritySiret");
		$this->expectOutputRegex("#Numéros SIRET - Bourg-en-Bresse#");
		$adminController->authoritySiretAction();
		$adminController->_actionAfter();
	}
	
	/**
	 * @preserveGlobalState disabled
	 * @runInSeparateProcess
	 */
	public function testAuthoritySiretActionNoId(){
		$this->setSuperAdminAuthentication();
		$adminController = new AdminController($this->getObjectInstancier());
		$this->setExpectedException("RedirectException","admin_authorities.php");
		$adminController->authoritySiretAction();
	}
	
	/**
	 * @preserveGlobalState disabled
	 * @runInSeparateProcess
	 */
	public function testOtherAuthority(){
		$this->setAdminCol2Authentication();
		$adminController = new AdminController($this->getObjectInstancier());
		$_GET['id'] = 1;
		$this->setExpectedException("RedirectException","Accès refusé");
		$adminController->authoritySiretAction();
	}
	
	/**
	 * @preserveGlobalState disabled
	 * @runInSeparateProcess
	 */
	public function testAddSiretNoValue(){
		$this->setSuperAdminAuthentication();
		$adminController = new AdminController($this->getObjectInstancier());
		$this->setExpectedException("RedirectException","admin_authorities.php");
		$adminController->authoritySiretAddAction();
	}
	
	/**
	 * @preserveGlobalState disabled
	 * @runInSeparateProcess
	 */
	public function testAddSiretBadSiret(){
		$this->setSuperAdminAuthentication();
		$adminController = new AdminController($this->getObjectInstancier());
		$_POST['authority_id'] = 1;
		$_POST['siret'] = "badsiret";
		$this->setExpectedException("RedirectException","admin_authority_siret.php?id=1&siret=badsiret");
		$adminController->authoritySiretAddAction();
	}
	
	/**
	 * @preserveGlobalState disabled
	 * @runInSeparateProcess
	 */
	public function testAddSiret(){
		$this->setSuperAdminAuthentication();
		$adminController = new AdminController($this->getObjectInstancier());
		$_POST['authority_id'] = 1;
		$_POST['siret'] = "06552185881996";
		$this->setExpectedException("RedirectException","admin_authority_siret.php?id=1");
		$adminController->authoritySiretAddAction();
	}
	
	/**
	 * @preserveGlobalState disabled
	 * @runInSeparateProcess
	 */
	public function testDelSiret(){
		$this->setSuperAdminAuthentication();
		$adminController = new AdminController($this->getObjectInstancier());
		$_POST['authority_id'] = 42;
		$this->setExpectedException("RedirectException","admin_authority_siret.php?");
		$adminController->authoritySiretDelAction();
	}
	
	
	
	
}