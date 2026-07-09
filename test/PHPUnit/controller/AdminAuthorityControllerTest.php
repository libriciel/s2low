<?php

use IntegrationTests\S2lowIntegrationTestCase;
use S2low\Enum\UserRole;
use S2lowLegacy\Class\actes\ActesConventions;
use S2lowLegacy\Controller\AdminAuthorityController;
use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Lib\ObjectInstancier;
use S2lowLegacy\Lib\RedirectException;

class AdminAuthorityControllerTest extends S2lowIntegrationTestCase
{
    /**
     * @throws RedirectException
     */
    public function testDownloadConventionAction()
    {
        $actesConvention = $this->getMockBuilder(ActesConventions::class)->disableOriginalConstructor()->getMock();
        $actesConvention->method("getConventionFilepath")->willReturn(
            __DIR__ . "/../class/fixtures/vide.pdf"
        );
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        self::getContainer()->get(Environnement::class)->get()->set('authority_id', 1);
        self::getContainer()->set(ActesConventions::class, $actesConvention);
        $adminAuthorityController = $this->getAdminAuthorityController();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('exit() called');
        $this->expectOutputRegex("##");
        $adminAuthorityController->downloadConventionAction();
    }


    /**
     * @throws RedirectException
     */
    public function testDownloadConventionActionNoConvention()
    {
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        self::getContainer()->get(Environnement::class)->get()->set('authority_id', 1);
        $adminAuthorityController = $this->getAdminAuthorityController();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Redirect to /admin/authorities/admin_authority_edit.php?id=1 with message : Impossible de récupérer la convention');

        $adminAuthorityController->downloadConventionAction();
    }

    /**
     * @throws RedirectException
     */
    public function testDownloadConventionActionNoAuthorityId()
    {
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $adminAuthorityController = self::getContainer()->get(AdminAuthorityController::class);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Redirect to');

        $adminAuthorityController->downloadConventionAction();
    }

    /**
     * @throws RedirectException
     */
    public function testExportListAction()
    {
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $adminAuthorityController = $this->getAdminAuthorityController();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('exit() called');
        $this->expectOutputRegex("#Bourg-en-Bresse#");
        $adminAuthorityController->exportListAction();
    }

    /**
     * @throws RedirectException
     */
    public function testExportListActionGroupAdmin()
    {
        $this->setUserWithRole(UserRole::AdministrateurGroupe);
        $adminAuthorityController = $this->getAdminAuthorityController();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('exit() called');
        $this->expectOutputRegex("#Bourg-en-Bresse#");
        $adminAuthorityController->exportListAction();
    }

    /**
     * @throws RedirectException
     */
    public function testExportListActionUser()
    {
        $this->setUserWithRole(UserRole::AdministrateurCollectivite);
        $adminAuthorityController = $this->getAdminAuthorityController();
        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage('Vous devez être administrateur de groupe ou super admin');
        $adminAuthorityController->exportListAction();
    }

    private function getAdminAuthorityController(): AdminAuthorityController
    {
        return new AdminAuthorityController(
            self::getContainer()->get(ObjectInstancier::class),
        );
    }
}
