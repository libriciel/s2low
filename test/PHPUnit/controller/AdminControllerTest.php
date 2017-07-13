<?php

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
		$this->setExpectedException("RedirectException","Redirect to");
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

	/**
	 * @preserveGlobalState disabled
	 * @runInSeparateProcess
	 */
	public function testAddSiretAPI(){
		$this->setSuperAdminAuthentication();
		$adminController = new AdminController($this->getObjectInstancier());
		$_POST['authority_id'] = 1;
		$_POST['siret'] = "06552185881996";
		$_POST['api'] = 1;
		$this->setExpectedException("Exception","Exit");
		$this->expectOutputRegex("#Num\\\u00e9ro SIRET ajout\\\u00e9#");
		$adminController->authoritySiretAddAction();
	}

	/**
	 * @preserveGlobalState disabled
	 * @runInSeparateProcess
	 */
	public function testListSiretApi(){

		$authoritySiret = new AuthoritySiretSQL($this->getSQLQuery());
		$authoritySiret->add(1, "12345678900014");

		$this->setSuperAdminAuthentication();
		$adminController = new AdminController($this->getObjectInstancier());
		$_GET['api'] = 1;
		$_GET['id'] = 1;
		$this->expectOutputRegex("#\[\"12345678900014\"\]#");
		$this->setExpectedException("Exception","exit() called");
		$adminController->authoritySiretAction();
	}

	public function testAuthoritiesAction(){
		$this->setSuperAdminAuthentication();
		$adminController = new AdminController($this->getObjectInstancier());
		$adminController->authoritiesAction();
		$this->assertEquals("Gestion des collectivités",$adminController->getViewParameter('titre'));
	}

	public function testAuthoritiesActionGroupAdmin(){
		$this->setAdminGroupAuthentication();
		$adminController = new AdminController($this->getObjectInstancier());
		$adminController->authoritiesAction();
		$this->assertEquals("Gestion des collectivités du groupe Groupe de test",$adminController->getViewParameter('titre'));

		$authorities = $adminController->getViewParameter('authorities');
		foreach ($authorities as $authority) {
			$this->assertEquals(1, $authority['authority_group_id']);
		}
	}

	/**
	 * @preserveGlobalState disabled
	 * @runInSeparateProcess
	 */
	public function testAuthoritiesActionAPI(){
		$_GET['api'] = 1;
		$this->setAdminGroupAuthentication();
		$adminController = new AdminController($this->getObjectInstancier());
		$this->setExpectedException("Exception","exit() called");
		$this->expectOutputRegex("##");
		$adminController->authoritiesAction();
		$out = $this->getActualOutput();
		$result = json_decode($out,true);
		$this->assertEquals(1,$result[1]['id']);
	}

}