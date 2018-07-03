<?php

class AdminServiceControllerTest extends S2lowTestCase {

	const NOM_SERVICE = 'mon service';

	/** @var AdminServiceController */
	private $adminServiceController;

	protected function setUp() {
		parent::setUp();
		$this->setSuperAdminAuthentication();
		$this->adminServiceController = $this->getObjectInstancier()->get(AdminServiceController::class);
	}

	/**
	 * @throws RedirectException
	 */
	public function testAddFailed(){
		$this->setExpectedException(Exception::class,"Le nom du service est obligatoire");
		$this->adminServiceController->addAction();
	}

	/**
	 * @throws RedirectException
	 */
	public function testAdd(){
		$this->setExpectedException("Exception");
		$this->expectOutputRegex('#Le service a #');
		$this->addService();
	}

	/**
	 * @throws RedirectException
	 */
	public function testAlreadyExists(){
		$serviceUser = $this->getObjectInstancier()->get(ServiceUser::class);
		$serviceUser->add(self::NOM_SERVICE,1);
		$this->setExpectedException("Exception");
		$this->expectOutputRegex('#Ce service existe#');
		$this->addService();
	}

	/**
	 * @throws RedirectException
	 */
	private function addService(){
		$this->getObjectInstancier()->get("Environnement")->post()->set('name',self::NOM_SERVICE);
		$this->getObjectInstancier()->get("Environnement")->post()->set('authority_id',1);
		$this->getObjectInstancier()->get("Environnement")->post()->set('api',1);
		$this->adminServiceController->addAction();
	}

	public function testListService(){
		$serviceUser = $this->getObjectInstancier()->get(ServiceUser::class);
		$serviceUser->add(self::NOM_SERVICE,1);
		$this->getObjectInstancier()->get("Environnement")->get()->set('authority_id',1);
		$this->setExpectedException(Exception::class,"exit() called");
		$this->expectOutputRegex("#\"name\":\"mon service\"#");
		$this->adminServiceController->listAction();
	}

	public function testAddUserAction(){
		$serviceUser = $this->getObjectInstancier()->get(ServiceUser::class);
		$serviceUser->add(self::NOM_SERVICE,1);
		$service_list = $serviceUser->getServiceUser(1);
		$id_service = $service_list[0]['id'];

		$this->getObjectInstancier()->get("Environnement")->post()->set('id_user',1);
		$this->getObjectInstancier()->get("Environnement")->post()->set('id_service',$id_service);
		$this->setExpectedException(RedirectException::class,"Redirect to");
		$this->adminServiceController->addUserAction();
	}

}