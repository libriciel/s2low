<?php

use IntegrationTests\S2lowIntegrationTestCase;
use S2low\Enum\UserRole;
use S2lowLegacy\Controller\AdminSAEController;
use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Lib\RedirectException;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\PastellProperties;

class AdminSAEControllerTest extends S2lowIntegrationTestCase
{
    public function testEditAction()
    {
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $adminServiceController = self::getContainer()->get(AdminSAEController::class);


        $authoritySQL = self::getContainer()->get(AuthoritySQL::class);
        $pastellProperties = new PastellProperties();
        $pastellProperties->url = "test";
        $pastellProperties->login = "login";
        $pastellProperties->password = "password";
        $pastellProperties->id_e = 42;

        $authoritySQL->updateSAE(1, $pastellProperties);

        self::getContainer()->get(Environnement::class)->get()->set('id', 1);

        $adminServiceController->_actionBefore("AdminSAE", "edit");
        $adminServiceController->editAction();  //BUG ??
        $this->expectOutputRegex("#Identifiant de l'entité#");
        $adminServiceController->_actionAfter();
    }

    /**
     * @throws RedirectException
     */
    public function testTestAction()
    {
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $adminServiceController = self::getContainer()->get(AdminSAEController::class);
        self::getContainer()->get(Environnement::class)->get()->set('id', 1);
        self::expectException(Exception::class);
        self::expectExceptionMessage("Redirect to");
        $adminServiceController->testAction();
    }
}
