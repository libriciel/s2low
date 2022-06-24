<?php

class HeliosPESRetourChangeStatusControllerTest extends S2lowTestCase
{
    public function testChangeStatusAction()
    {
        $this->setUserAuthentification();
        $heliosRetourSQL = $this->getObjectInstancier()->get(HeliosRetourSQL::class);

        $transaction_id = $heliosRetourSQL->add(1, "000000000", "toto.xml");

        $this->getObjectInstancier()->get("Environnement")->get()->set('id', $transaction_id);

        $info = $heliosRetourSQL->getInfo($transaction_id);
        $this->assertEquals(0, $info['status']);

        $heliosPESRetourChangeStatusController = $this->getObjectInstancier()->get(HeliosPESRetourChangeStatusController::class);
        ob_start();
        try {
            $heliosPESRetourChangeStatusController->changeStatusAction();
        } catch (Exception $e) {
            /*Nothing to do*/
        }
        ob_end_clean();
        $info = $heliosRetourSQL->getInfo($transaction_id);
        $this->assertEquals(1, $info['status']);
    }

    public function testChangeStatusActionNotGoodCollectivite()
    {
        $this->setAdminCol2Authentication();
        $heliosRetourSQL = $this->getObjectInstancier()->get(HeliosRetourSQL::class);

        $transaction_id = $heliosRetourSQL->add(1, "000000000", "toto.xml");

        $this->getObjectInstancier()->get("Environnement")->get()->set('id', $transaction_id);

        $info = $heliosRetourSQL->getInfo($transaction_id);
        $this->assertEquals(0, $info['status']);

        $heliosPESRetourChangeStatusController = $this->getObjectInstancier()->get(HeliosPESRetourChangeStatusController::class);
        $this->expectException(Exception::class);
        $this->expectOutputRegex("#KO#");
        $heliosPESRetourChangeStatusController->changeStatusAction();
    }
}
