<?php

class AdminAuthorityControllerTest extends S2lowTestCase {

    /**
     * @preserveGlobalState disabled
     * @runInSeparateProcess
	 * @throws RedirectException
     */
    public function testDownloadConventionAction(){
        $actesConvention = $this->getMockBuilder("ActesConventions")->disableOriginalConstructor()->getMock();
        $actesConvention->method("getConventionFilepath")->willReturn(
            __DIR__."/../class/fixtures/vide.pdf"
        );

        $this->getObjectInstancier()->set("ActesConventions",$actesConvention);

        $this->setSuperAdminAuthentication();
        $this->getObjectInstancier()->get("Environnement")->get()->set('authority_id',1);
        $adminAuthorityController = $this->getObjectInstancier()->get("AdminAuthorityController");

        $this->setExpectedException("Exception","exit() called");
        $this->expectOutputRegex("##");
        $adminAuthorityController->downloadConventionAction();
    }


	/**
	 * @throws RedirectException
	 */
    public function testDownloadConventionActionNoConvention(){
        $this->setSuperAdminAuthentication();
        $this->getObjectInstancier()->get("Environnement")->get()->set('authority_id',1);
        $adminAuthorityController = $this->getObjectInstancier()->get("AdminAuthorityController");
        $this->setExpectedException(
            "Exception",
            "Redirect to /admin/authorities/admin_authority_edit.php?id=1 with message : Impossible de récupérer la convention"
        );
        $adminAuthorityController->downloadConventionAction();
    }

	/**
	 * @throws RedirectException
	 */
    public function testDownloadConventionActionNoAuthorityId(){
        $this->setSuperAdminAuthentication();
        $adminAuthorityController = $this->getObjectInstancier()->get("AdminAuthorityController");
        $this->setExpectedException(
            "Exception",
            "Redirect to"
        );
        $adminAuthorityController->downloadConventionAction();
    }

	/**
	 * @throws RedirectException
	 */
    public function testExportListAction(){
		$this->setSuperAdminAuthentication();
		$adminAuthorityController = $this->getObjectInstancier()->get(AdminAuthorityController::class);
		$this->setExpectedException(Exception::class,"exit() called");
		$this->expectOutputRegex("#Bourg-en-Bresse#");
		$adminAuthorityController->exportListAction();
	}

	/**
	 * @throws RedirectException
	 */
	public function testExportListActionGroupAdmin(){
		$this->setAdminGroupAuthentication();
		$adminAuthorityController = $this->getObjectInstancier()->get(AdminAuthorityController::class);
		$this->setExpectedException(Exception::class,"exit() called");
		$this->expectOutputRegex("#Bourg-en-Bresse#");
		$adminAuthorityController->exportListAction();
	}

	/**
	 * @throws RedirectException
	 */
	public function testExportListActionUser(){
		$this->setAdminColAuthentication();
		$adminAuthorityController = $this->getObjectInstancier()->get(AdminAuthorityController::class);
		$this->setExpectedException(RedirectException::class,"Vous devez être administrateur de groupe ou super admin");
		$adminAuthorityController->exportListAction();
	}

}