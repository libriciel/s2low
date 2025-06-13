<?php

use IntegrationTests\S2lowIntegrationTestCase;
use PHPUnit\ActesUtilitiesTestTrait;
use S2low\Enum\UserRole;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\Database;
use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Lib\FrontController;

class ActesExportControllerTest extends S2lowIntegrationTestCase
{
    use ActesUtilitiesTestTrait;

    public function testIndexAction()
    {
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $frontController = self::getContainer()->get(FrontController::class);
        $this->expectOutputRegex("#Bourg-en-Bresse#");
        $frontController->go("ActesExport", "index");
    }

    public function testHandlerIntervalTooBig()
    {
        $this->setUserWithRole(UserRole::SuperAdministrateur);

        $environnement = self::getContainer()->get(Environnement::class);
        $environnement->get()->set('date_debut', '2023-01-01');
        $environnement->get()->set('date_fin', date("Y-m-d 23:59:59"));
        $environnement->get()->set('authority_id', 123);

        $frontController = self::getContainer()->get(FrontController::class);

        $frontController->go("ActesExport", "handler");

        $session = $environnement->session();
        $this->assertEquals(
            "La récupération est limitée à un intervalle de 400 jours",
            $session->get('error')
        );
    }

    public function testHandler()
    {
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $transactionId  = $this->createTransaction(
            status: 1,
            archivePath: $this->projectDir . "/test/PHPUnit/class/actes/fixtures/abc-TACT--000000000--20170803-16.tar.gz",
            submissionDate: date("Y-m-d")
        );
        $envelopeId = self::getContainer()->get(Database::class)->getOneLine('select envelope_id from actes_transactions where id = ?', [$transactionId]);

        $environnement = self::getContainer()->get(Environnement::class);
        $yesterday = new DateTime('yesterday');
        $environnement->get()->set('date_debut', $yesterday->format('Y-m-d'));
        $environnement->get()->set('date_fin', date("Y-m-d 23:59:59"));
        $environnement->get()->set('authority_id', 101);

        $frontController = self::getContainer()->get(FrontController::class);

        $this->expectException("Exception");
        $this->expectExceptionMessage("exit() called");
        $this->expectOutputRegex("#$envelopeId,#");
        $frontController->go("ActesExport", "handler");
    }

    protected function getActesTransactionsSQL(): ActesTransactionsSQL
    {
        return self::getContainer()->get(ActesTransactionsSQL::class);
    }
}
