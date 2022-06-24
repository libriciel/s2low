<?php

class ActesExportControllerTest extends S2lowTestCase
{
    public function testIndexAction()
    {
        $this->setSuperAdminAuthentication();
        $frontController = $this->getObjectInstancier()->get("FrontController");
        $this->expectOutputRegex("#Bourg-en-Bresse#");
        $frontController->go("ActesExport", "index");
    }

    public function testHandlerIntervalTooBig()
    {
        $this->setSuperAdminAuthentication();
        $this->getObjectInstancier()->get("Environnement")->get()->set(
            'date_debut',
            date(
                "Y-m-d",
                strtotime(
                    sprintf("- %d days", ActesExportController::MAX_EXPORT_INTERVAL_IN_DAY + 1)
                )
            )
        );
        $this->getObjectInstancier()->get("Environnement")->get()->set('date_fin', date("Y-m-d"));
        $frontController = $this->getObjectInstancier()->get("FrontController");
        $frontController->go("ActesExport", "handler");
        $this->assertEquals(
            "La récupération est limitée à un intervalle de 400 jours",
            $this->getObjectInstancier()->get("Environnement")->session()->get("error")
        );
    }

    public function testHandler()
    {
        $id_envelope  = $this->getObjectInstancier()->get("ActesEnvelopeSQL")->create(
            1,
            "000000000/20170721D/abc-EACT--210703385--20170612-2.tar.gz"
        );
        $this->setSuperAdminAuthentication();
        $this->getObjectInstancier()->get("Environnement")->get()->set(
            'date_debut',
            date(
                "Y-m-d",
                strtotime(
                    "yesterday"
                )
            )
        );

        $this->getObjectInstancier()->get("Environnement")->get()->set('date_fin', date("Y-m-d 23:59:59"));
        $frontController = $this->getObjectInstancier()->get("FrontController");
        $this->setExpectedException("Exception", "exit() called");
        $this->expectOutputRegex("#$id_envelope,#");
        $frontController->go("ActesExport", "handler");
    }
}
