<?php

use S2lowLegacy\Class\actes\ActesConventions;
use S2lowLegacy\Class\ActesWorkspace;
use S2lowLegacy\Controller\AdminAuthorityController;
use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Lib\RedirectException;

class AdminAuthorityControllerTest extends S2lowTestCase
{
    /**
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     * @throws RedirectException
     */
    public function testDownloadConventionAction()
    {
        $actesConvention = $this->getMockBuilder(ActesConventions::class)->disableOriginalConstructor()->getMock();
        $actesConvention->method("getConventionFilepath")->willReturn(
            __DIR__ . "/../class/fixtures/vide.pdf"
        );

        $this->getObjectInstancier()->set(ActesConventions::class, $actesConvention);

        $this->setSuperAdminAuthentication();
        $this->getObjectInstancier()->get(Environnement::class)->get()->set('authority_id', 1);
        $adminAuthorityController = $this->getObjectInstancier()->get(AdminAuthorityController::class);

        $this->setExpectedException("Exception", "exit() called");
        $this->expectOutputRegex("##");
        $adminAuthorityController->downloadConventionAction();
    }


    /**
     * @throws RedirectException
     * @throws \Exception
     */
    public function testDownloadConventionActionNoConvention()
    {
        $this->setSuperAdminAuthentication();
        $this->getObjectInstancier()->get(Environnement::class)->get()->set('authority_id', 1);
        $this->getObjectInstancier()->set(ActesWorkspace::class, $this->actesWorkspaceManager->get());
        $adminAuthorityController = $this->getObjectInstancier()->get(AdminAuthorityController::class);
        $this->setExpectedException(
            "Exception",
            "Redirect to /admin/authorities/admin_authority_edit.php?id=1 with message : Impossible de récupérer la convention"
        );
        $adminAuthorityController->downloadConventionAction();
    }

    /**
     * @throws RedirectException
     */
    public function testDownloadConventionActionNoAuthorityId()
    {
        $this->setSuperAdminAuthentication();
        $adminAuthorityController = $this->getObjectInstancier()->get(AdminAuthorityController::class);
        $this->setExpectedException(
            "Exception",
            "Redirect to"
        );
        $adminAuthorityController->downloadConventionAction();
    }

    /**
     * @throws RedirectException
     */
    public function testExportListAction()
    {
        $this->setSuperAdminAuthentication();
        $adminAuthorityController = $this->getObjectInstancier()->get(AdminAuthorityController::class);
        $this->setExpectedException(Exception::class, "exit() called");
        $this->expectOutputRegex("#Bourg-en-Bresse#");
        $adminAuthorityController->exportListAction();
    }

    /**
     * @throws RedirectException
     */
    public function testExportListActionGroupAdmin()
    {
        $this->setAdminGroupAuthentication();
        $adminAuthorityController = $this->getObjectInstancier()->get(AdminAuthorityController::class);
        $this->setExpectedException(Exception::class, "exit() called");
        $this->expectOutputRegex("#Bourg-en-Bresse#");
        $adminAuthorityController->exportListAction();
    }

    /**
     * @throws RedirectException
     */
    public function testExportListActionUser()
    {
        $this->setAdminColAuthentication();
        $adminAuthorityController = $this->getObjectInstancier()->get(AdminAuthorityController::class);
        $this->setExpectedException(RedirectException::class, "Vous devez être administrateur de groupe ou super admin");
        $adminAuthorityController->exportListAction();
    }
}
