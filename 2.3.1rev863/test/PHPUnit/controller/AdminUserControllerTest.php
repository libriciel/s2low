<?php


class AdminUserControllerTest extends S2lowTestCase {

	/**
	 * @var AdminUserController
	 */
	private $adminUserController;
	private $testStreamUrl;

	protected function setUp(){
		parent::setUp();
		$_FILES = array();
		$_POST = array();
		org\bovigo\vfs\vfsStream::setup("test");
		$this->testStreamUrl = org\bovigo\vfs\vfsStream::url("test");
		$this->adminUserController = new AdminUserController($this->getObjectInstancier());
	}

	private function setDataOk(){
		$this->setSuperAdminAuthentication();
		$this->setOnlyDataOk();
	}

	private function setOnlyDataOk(){
		$certificate_file = $this->testStreamUrl."/user1.pem";
		copy(__DIR__."/fixtures/user1.pem",$certificate_file);

		$_FILES['certificate'] = array('name'=>'user1.pem','tmp_name'=>$certificate_file,'size'=>filesize($certificate_file));
		$_FILES['certificate_rgs_2_etoiles'] = array('name'=>'user1.pem','tmp_name'=>$certificate_file,'size'=>filesize($certificate_file));

		$_POST['authority_id'] = 1;
		$_POST['email'] = 'eric@sigmalis.com';
		$_POST['name'] = 'Pommateau';
		$_POST['givenname'] = 'Eric';
		$_POST['status']= UserSQL::STATUS_ACTIVE;
	}

	public function testDoEdit(){
		$this->setDataOk();
		$this->adminUserController->doEditAction();
	}

	public function testDoEditFailed(){
		$this->setExpectedException("Exception","Message : Le certificat n'est pas valide");
		$this->adminUserController->doEditAction();
	}

	/**
	 * @preserveGlobalState disabled
	 * @runInSeparateProcess
	 */
	public function testDoEditApi(){
		$this->setDataOk();
		$_POST['api'] = 1;
		$this->expectOutputRegex("#Cr\\\u00e9ation de l'utilisateur Eric Pommateau#");
		$this->setExpectedException("Exception","Exit !");
		$this->adminUserController->doEditAction();
	}

	public function testDoEditApiFailed(){
		$_POST['api'] = 1;
		$this->setExpectedException("Exception","Le certificat n'est pas valide");
		$this->expectOutputRegex("#KO\nLe certificat n'est pas valide#");
		$this->adminUserController->doEditAction();
	}

	public function testDoEditModifNotExistingUser(){
		$this->setDataOk();
		$_POST['id'] = 42;
		$this->setExpectedException("Exception","Erreur lors de la modification de l'utilisateur");
		$this->adminUserController->doEditAction();
	}

	public function testDoEditModifNoRight(){
		$this->setUserAuthentification();
		$this->setOnlyDataOk();
		$this->setExpectedException("Exception","Redirect");
		$this->adminUserController->doEditAction();
	}

	public function testDoEditNoGroupIdForGroupAdmin(){
		$this->setAdminGroupAuthentication();
		$this->setOnlyDataOk();
		$this->setExpectedException("Exception","La collectivité n'appartient pas au groupe courant");
		$this->adminUserController->doEditAction();
	}

	/**
	 * @preserveGlobalState disabled
	 * @runInSeparateProcess
	 */
	public function testDoEditAuthorityIdMandatoryInApi(){
		$this->setDataOk();
		unset($_POST['authority_id']);
		$_POST['api'] = 1;
		$this->setExpectedException("Exception","Exit !");
		$this->expectOutputRegex("#authority_id est obligatoire#");
		$this->adminUserController->doEditAction();
	}

	public function testNotRightToModify(){
		$this->setAdminCol2Authentication();
		$this->setOnlyDataOk();
		$_POST['id'] = 1;
		$this->setExpectedException("Exception","Accès refusé pour la modification de cet utilisateur");
		$this->adminUserController->doEditAction();
	}

	public function testCreateGADMWithoutGroupId(){
		$this->setDataOk();
		$_POST['role'] = 'GADM';
		$this->setExpectedException("Exception","Vous devez indiquez un groupe pour créer un administrateur de groupe");
		$this->adminUserController->doEditAction();
	}

	public function testPasswordsDontMatch(){
		$this->setDataOk();
		$_POST['password'] = "ku9eiBae";
		$_POST['password2'] = "Ce6vohya";
		$this->setExpectedException("Exception"," Les mots de passe ne correspondent pas");
		$this->adminUserController->doEditAction();
	}

	public function testNoConnexionCertificate(){
		$this->setDataOk();
		$_FILES['certificate']['tmp_name'] = '';
		$this->setExpectedException("Exception","Le certificat utilisateur est obligatoire");
		$this->adminUserController->doEditAction();
	}

	public function testCloneWithoutLogin(){
		$this->setDataOk();
		$_POST['new_id'] = 8;
		$this->setExpectedException("Exception","Le login et le mot de passe sont obligatoire pour cloner un certificat");
		$this->adminUserController->doEditAction();
	}

	public function testClone(){
		$this->setDataOk();
		$_POST['new_id'] = 8;
		$_POST['login'] = 'alice';
		$_POST['password'] = 'eey3fo4A';
		$_POST['password2'] = 'eey3fo4A';
		$this->adminUserController->doEditAction();
	}

	public function testCloneSameCertificate(){
		$this->setDataOk();
		$this->adminUserController->doEditAction();
		$_POST['new_id'] = 8;
		$_POST['login'] = 'alice';
		$_POST['password'] = 'eey3fo4A';
		$_POST['password2'] = 'eey3fo4A';
		$this->setExpectedException("Exception", "Un utilisateur avec les mêmes données de certificat existe déjà. Vous pouvez mettre un login/mot de passe pour les différencier");
		$this->adminUserController->doEditAction();
	}

	public function testModifGroupAdmin(){
		$this->setOnlyDataOk();
		$this->setAdminGroupAuthentication();
		$_POST['login'] = 'bob';
		$_POST['password'] = 'eey3fo4A';
		$_POST['password2'] = 'eey3fo4A';
		$_POST['id'] = 6;
		$this->adminUserController->doEditAction();
	}

	public function testCreateDifferentAuthorities(){
		$this->setOnlyDataOk();
		$this->setAdminCol2Authentication();
		$_POST['authority_id'] = 1;
		$this->adminUserController->doEditAction();
	}

	public function testSetLogin(){
		$this->setDataOk();
		$_POST['login'] = 'alice';
		$_POST['password'] = 'eey3fo4A';
		$_POST['password2'] = 'eey3fo4A';
		$this->adminUserController->doEditAction();
		$this->setExpectedException("Exception","Un utilisateur avec les même information de connexion et d'identification existe dans la base S2low");
		$this->adminUserController->doEditAction();
	}

	public function testSetInGroupOK(){
		$this->setOnlyDataOk();
		$this->setAdminGroupAuthentication();
		$_POST['authority_id'] = 2;
		$this->adminUserController->doEditAction();
	}

	public function testForceRole(){
		$this->setOnlyDataOk();
		$this->setAdminGroupAuthentication();
		$_POST['authority_id'] = 2;
		$_POST['role'] = 'SADM';
		$user_id = $this->adminUserController->doEditAction();
		$userSQL = new UserSQL($this->getSQLQuery());
		$user_info = $userSQL->getInfo($user_id);
		$this->assertEquals('ADM',$user_info['role']);
	}

	public function testNotGoodRGSEtoile(){
		$this->setDataOk();
		file_put_contents($this->testStreamUrl."/rogue.pem","bad certificate");
		$_FILES['certificate_rgs_2_etoiles']['tmp_name'] = $this->testStreamUrl."/rogue.pem";
		$this->setExpectedException("Exception"," Impossible de lire le certificat");
		$this->adminUserController->doEditAction();
	}

	public function testSameInfo(){
		$this->setDataOk();
		$this->adminUserController->doEditAction();
		$this->setExpectedException("Exception","Un utilisateur avec les même information de connexion et d'identification existe dans la base S2low");
		$this->adminUserController->doEditAction();
	}

}