<?php

use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Lib\RedirectException;
use S2lowLegacy\Model\AuthoritySiretSQL;

class AdminControllerTest extends S2lowTestCase
{
    public function testActionBefore()
    {
        $this->setSuperAdminAuthentication();
        $adminController = $this->getObjectInstancier()->get(AdminController::class);
        $adminController->_actionBefore("Mock", "mock");
        $this->assertTrue(true);
    }

    public function testAuthoritySiretAction()
    {
        $this->getObjectInstancier()->get(Environnement::class)->get()->set('id', 1);

        $this->setSuperAdminAuthentication();
        $adminController = $this->getObjectInstancier()->get(AdminController::class);
        $adminController->authoritySiretAction();
        $this->assertEquals(1, $adminController->authority_info['id']);
    }

    public function testAuthoritySiretTemplate()
    {
        $this->getObjectInstancier()->get(Environnement::class)->get()->set('id', 1);

        $this->setSuperAdminAuthentication();
        $adminController = $this->getObjectInstancier()->get(AdminController::class);
        $adminController->_actionBefore("Admin", "authoritySiret");
        $this->expectOutputRegex("#Numéros SIRET - Bourg-en-Bresse#");
        $adminController->authoritySiretAction();
        $adminController->_actionAfter();
    }


    public function testAuthoritySiretActionNoId()
    {
        $this->setSuperAdminAuthentication();
        $adminController = $this->getObjectInstancier()->get(AdminController::class);
        $this->setExpectedException(RedirectException::class, "admin_authorities.php");
        $adminController->authoritySiretAction();
    }

    public function testOtherAuthority()
    {
        $this->setAdminCol2Authentication();
        $adminController = $this->getObjectInstancier()->get(AdminController::class);
        $_GET['id'] = 1;
        $this->setExpectedException(RedirectException::class, "Redirect to");
        $adminController->authoritySiretAction();
    }

    public function testAddSiretNoValue()
    {
        $this->setSuperAdminAuthentication();
        $adminController = $this->getObjectInstancier()->get(AdminController::class);
        $this->setExpectedException(RedirectException::class, "admin_authorities.php");
        $adminController->authoritySiretAddAction();
    }

    public function testAddSiretBadSiret()
    {
        $this->getObjectInstancier()->get(Environnement::class)->post()->set('authority_id', 1);
        $this->getObjectInstancier()->get(Environnement::class)->post()->set('siret', 'badsiret');

        $this->setSuperAdminAuthentication();
        $adminController = $this->getObjectInstancier()->get(AdminController::class);

        $this->setExpectedException(RedirectException::class, "admin_authority_siret.php?id=1&siret=badsiret");
        $adminController->authoritySiretAddAction();
    }

    public function testAddSiret()
    {
        $this->getObjectInstancier()->get(Environnement::class)->post()->set('authority_id', 1);
        $this->getObjectInstancier()->get(Environnement::class)->post()->set('siret', '06552185881996');

        $this->setSuperAdminAuthentication();
        $adminController = new AdminController($this->getObjectInstancier());
        $this->setExpectedException(RedirectException::class, "admin_authority_siret.php?id=1");
        $adminController->authoritySiretAddAction();
    }

    public function testDelSiret()
    {
     //Migration php 8 : ce test ne fonctionnait pas ...
        $this->getObjectInstancier()->get(Environnement::class)->post()->set('authority_siret_id', "06552185881996");

        $authoritySiret = new AuthoritySiretSQL($this->getSQLQuery());
        $authority_siret_id = $authoritySiret->add(1, "06552185881996");

        $this->getObjectInstancier()->get(Environnement::class)->post()->set('authority_siret_id', $authority_siret_id);

        $this->setSuperAdminAuthentication();
        $adminController = $this->getObjectInstancier()->get(AdminController::class);
        $this->setExpectedException(RedirectException::class, "admin_authority_siret.php?");
        $adminController->authoritySiretDelAction();
    }

    public function testAddSiretAPI()
    {
        $this->getObjectInstancier()->get(Environnement::class)->post()->set('authority_id', 1);
        $this->getObjectInstancier()->get(Environnement::class)->post()->set('siret', "06552185881996");
        $this->getObjectInstancier()->get(Environnement::class)->post()->set('api', 1);
        $this->setSuperAdminAuthentication();
        $adminController = $this->getObjectInstancier()->get("AdminController");

        $this->setExpectedException("Exception", "Exit");
        $this->expectOutputRegex("#Num\\\u00e9ro SIRET ajout\\\u00e9#");
        $adminController->authoritySiretAddAction();
    }

    public function testListSiretApi()
    {

        $this->getObjectInstancier()->get(Environnement::class)->get()->set('api', 1);
        $this->getObjectInstancier()->get(Environnement::class)->get()->set('id', "1");

        $authoritySiret = new AuthoritySiretSQL($this->getSQLQuery());
        $authoritySiret->add(1, "12345678900014");

        $this->setSuperAdminAuthentication();
        $adminController = $this->getObjectInstancier()->get(AdminController::class);

        $this->expectOutputRegex("#\[\"12345678900014\"\]#");
        $this->setExpectedException("Exception", "exit() called");
        $adminController->authoritySiretAction();
    }

    public function testAuthoritiesAction()
    {
        $this->setSuperAdminAuthentication();
        $adminController = $this->getObjectInstancier()->get(AdminController::class);
        $adminController->authoritiesAction();
        $this->assertEquals("Gestion des collectivités", $adminController->getViewParameter('titre'));
    }

    public function testAuthoritiesActionGroupAdmin()
    {
        $this->setAdminGroupAuthentication();
        $adminController = $this->getObjectInstancier()->get(AdminController::class);
        $adminController->authoritiesAction();
        $this->assertEquals("Gestion des collectivités du groupe Groupe de test", $adminController->getViewParameter('titre'));

        $authorities = $adminController->getViewParameter('authorities');
        foreach ($authorities as $authority) {
            $this->assertEquals(1, $authority['authority_group_id']);
        }
    }

    public function testAuthoritiesActionAPI()
    {
        $this->getObjectInstancier()->get(Environnement::class)->get()->set('api', 1);

        $this->setAdminGroupAuthentication();
        $adminController = $this->getObjectInstancier()->get(AdminController::class);
        $this->setExpectedException("Exception", "exit() called");
        $this->expectOutputRegex("##");
        $adminController->authoritiesAction();
        $out = $this->getActualOutput();
        $result = json_decode($out, true);
        $this->assertEquals(1, $result[1]['id']);
    }
}
