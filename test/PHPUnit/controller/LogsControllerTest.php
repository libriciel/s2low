<?php

use IntegrationTests\S2lowIntegrationTestCase;
use S2low\Enum\UserRole;
use S2lowLegacy\Class\Log;
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
        $this->expectOutputRegex("/Tedetis : Journal d&#039;évènements/");
        $logsController->_actionAfter();
    }

    public function testTitleAdminGroup()
    {
        $this->setUserWithRole(UserRole::AdministrateurGroupe);
        $logsController = self::getContainer()->get(LogsController::class);
        $logsController->viewAction();
        $h1_title_expected = "Journal d'évènements du groupe «&nbsp;Groupe de test&nbsp;»";
        $this->assertEquals($h1_title_expected, $logsController->h1_title);
    }

    public function testModuleFilterOfAGroupAdminLeavesOutTheModuleItDoesNotAdminister(): void
    {
        $this->setUserWithRole(UserRole::AdministrateurGroupe);
        $this->getSQLQuery()->query('UPDATE authorities SET actes_group_id = 2, helios_group_id = 1');
        $logsController = self::getContainer()->get(LogsController::class);

        $logsController->viewAction();

        $this->assertSame(['helios', 'mail'], array_column($logsController->module_list, 'name'));
    }

    public function testGroupAdminForcingTheModuleItDoesNotAdministerGetsNoLog(): void
    {
        $this->setUserWithRole(UserRole::AdministrateurGroupe);
        $this->getSQLQuery()->query('UPDATE authorities SET actes_group_id = 2, helios_group_id = 1');
        Log::newEntry('test', 'message actes', 1, false, 'USER', 'actes', false, 1);
        $logsController = self::getContainer()->get(LogsController::class);
        $logsController->getRecuperateurGet()->set('module', 'actes');

        $logsController->viewAction();

        $this->assertSame([], $logsController->logs_list);
    }

    public function testTitleAdminCol()
    {
        $this->logAs(13);
        $this->setUserWithRole(UserRole::AdministrateurCollectivite);
        $this->setUserAuthority(2);

        $logsController = self::getContainer()->get(LogsController::class);
        $logsController->viewAction();
        $h1_title_expected = "Journal d'évènements de la collectivité «&nbsp;Saint-Andre de Corcy&nbsp;»";
        $this->assertEquals($h1_title_expected, $logsController->h1_title);
    }

    public function testUser()
    {
        $this->setUserWithRole(UserRole::Utilisateur);
        $logsController = self::getContainer()->get(LogsController::class);
        $logsController->viewAction();
        $h1_title_expected = "Journal d'évènements";
        $this->assertEquals($h1_title_expected, $logsController->h1_title);
    }
}
