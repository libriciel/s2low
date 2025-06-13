<?php

use IntegrationTests\S2lowIntegrationTestCase;
use S2low\Enum\UserRole;
use S2lowLegacy\Controller\LogsController;

class LogsControllerTest extends S2lowIntegrationTestCase
{
    public function testViewAction()
    {
        $_SERVER["QUERY_STRING"] = "";
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $logsController = self::getContainer()->get(LogsController::class);
        $logsController->_actionBefore("Logs", "view");
        $logsController->viewAction();
        $this->expectOutputRegex("#Tedetis : Journal d'évènements#");
        $logsController->_actionAfter();
    }

    public function testTitleAdminGroup()
    {
        $this->setUserWithRole(UserRole::AdministrateurGroupe);
        $logsController = self::getContainer()->get(LogsController::class);
        $logsController->viewAction();
        $h1_title_expected = "Journal d'évènements du groupe «&nbsp;Groupe de test&nbsp;»";
        $this->assertEquals($h1_title_expected, $logsController->getViewParameter('h1_title'));
    }

    public function testTitleAdminCol()
    {
        $this->logAs(113);
        $this->setUserWithRole(UserRole::AdministrateurCollectivite);
        $this->setUserAuthority(102);

        $logsController = self::getContainer()->get(LogsController::class);
        $logsController->viewAction();
        $h1_title_expected = "Journal d'évènements de la collectivité «&nbsp;Saint-Andre de Corcy&nbsp;»";
        $this->assertEquals($h1_title_expected, $logsController->getViewParameter('h1_title'));
    }

    public function testUser()
    {
        $this->setUserWithRole(UserRole::Utilisateur);
        $logsController = self::getContainer()->get(LogsController::class);
        $logsController->viewAction();
        $h1_title_expected = "Journal d'évènements";
        $this->assertEquals($h1_title_expected, $logsController->getViewParameter('h1_title'));
    }
}
