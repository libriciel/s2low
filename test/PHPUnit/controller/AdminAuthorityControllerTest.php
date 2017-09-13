<?php

class AdminAuthorityControllerTest extends S2lowTestCase {

    /**
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     */
    public function testDownloadConventionAction(){
        $actesConvention = $this->getMockBuilder("ActesConventions")->disableOriginalConstructor()->getMock();
        $actesConvention->expects($this->any())->method("getConventionFilepath")->willReturn(
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

    public function testDownloadConventionActionNoAuthorityId(){
        $this->setSuperAdminAuthentication();
        $adminAuthorityController = $this->getObjectInstancier()->get("AdminAuthorityController");
        $this->setExpectedException(
            "Exception",
            "Redirect to"
        );
        $adminAuthorityController->downloadConventionAction();
    }



}