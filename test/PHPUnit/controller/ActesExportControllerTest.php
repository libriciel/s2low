<?php

declare(strict_types=1);

namespace PHPUnit\controller;

use PHPUnit\S2lowTestCase;
use S2lowLegacy\Class\actes\ActesEnvelopeSQL;
use S2lowLegacy\Controller\ActesExportController;
use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Lib\FrontController;

class ActesExportControllerTest extends S2lowTestCase
{
    public function testIndexAction()
    {
        $this->setSuperAdminAuthentication();
        $frontController = $this->getObjectInstancier()->get(FrontController::class);
        $this->expectOutputRegex("#Bourg-en-Bresse#");
        $frontController->go("ActesExport", "index");
    }

    public function testHandlerIntervalTooBig()
    {
        $this->setSuperAdminAuthentication();
        $this->getObjectInstancier()->get(Environnement::class)->get()->set(
            'date_debut',
            date(
                "Y-m-d",
                strtotime(
                    sprintf("- %d days", ActesExportController::MAX_EXPORT_INTERVAL_IN_DAY + 1)
                )
            )
        );
        $this->getObjectInstancier()->get(Environnement::class)->get()->set('date_fin', date("Y-m-d"));
        $frontController = $this->getObjectInstancier()->get(FrontController::class);
        $frontController->go("ActesExport", "handler");
        $this->assertEquals(
            "La récupération est limitée à un intervalle de 400 jours",
            $this->getObjectInstancier()->get(Environnement::class)->session()->get("error")
        );
    }

    public function testHandler()
    {
        $id_envelope  = $this->getObjectInstancier()->get(ActesEnvelopeSQL::class)->create(
            1,
            "000000000/20170721D/abc-EACT--210703385--20170612-2.tar.gz"
        );
        $this->setSuperAdminAuthentication();
        $this->getObjectInstancier()->get(Environnement::class)->get()->set(
            'date_debut',
            date(
                "Y-m-d",
                strtotime(
                    "yesterday"
                )
            )
        );

        $this->getObjectInstancier()->get(Environnement::class)->get()->set('date_fin', date("Y-m-d 23:59:59"));
        $frontController = $this->getObjectInstancier()->get(FrontController::class);
        $this->setExpectedException("Exception", "exit() called");
        $this->expectOutputRegex("#$id_envelope,#");
        $frontController->go("ActesExport", "handler");
    }
}
