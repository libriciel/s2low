<?php

use S2lowLegacy\Controller\AdminSAEController;
use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Lib\RedirectException;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\PastellProperties;

class AdminSAEControllerTest extends S2lowTestCase
{
    public function testEditAction()
    {
        $this->setSuperAdminAuthentication();
        $adminServiceController = $this->getObjectInstancier()->get(AdminSAEController::class);


        $authoritySQL = new AuthoritySQL($this->getSQLQuery());
        $pastellProperties = new PastellProperties();
        $pastellProperties->url = "test";
        $pastellProperties->login = "login";
        $pastellProperties->password = "password";
        $pastellProperties->id_e = 42;

        $authoritySQL->updateSAE(1, $pastellProperties);

        $this->getObjectInstancier()->get(Environnement::class)->get()->set('id', 1);

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
        $this->setSuperAdminAuthentication();
        $adminServiceController = $this->getObjectInstancier()->get(AdminSAEController::class);
        $this->getObjectInstancier()->get(Environnement::class)->get()->set('id', 1);
        $this->setExpectedException(Exception::class, "Redirect to");
        $adminServiceController->testAction();
    }
}
