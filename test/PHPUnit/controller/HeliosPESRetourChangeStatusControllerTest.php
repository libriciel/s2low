<?php

use IntegrationTests\S2lowIntegrationTestCase;
use S2low\Enum\UserRole;
use S2lowLegacy\Controller\HeliosPESRetourChangeStatusController;
use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Model\HeliosRetourSQL;

class HeliosPESRetourChangeStatusControllerTest extends S2lowIntegrationTestCase
{
    public function testChangeStatusAction()
    {
        $this->setUserWithRole(UserRole::Utilisateur);
        $heliosRetourSQL = self::getContainer()->get(HeliosRetourSQL::class);

        $transaction_id = $heliosRetourSQL->add(101, "000000000", "toto.xml", 10, "sha1");

        self::getContainer()->get(Environnement::class)->get()->set('id', $transaction_id);

        $info = $heliosRetourSQL->getInfo($transaction_id);
        $this->assertEquals(0, $info['status']);

        $heliosPESRetourChangeStatusController = self::getContainer()->get(HeliosPESRetourChangeStatusController::class);
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
        $this->setUserWithRole(UserRole::AdministrateurCollectivite);
        $heliosRetourSQL = self::getContainer()->get(HeliosRetourSQL::class);

        $transaction_id = $heliosRetourSQL->add(102, "000000000", "toto.xml", 10, "sha1");

        self::getContainer()->get(Environnement::class)->get()->set('id', $transaction_id);

        $info = $heliosRetourSQL->getInfo($transaction_id);
        $this->assertEquals(0, $info['status']);

        $heliosPESRetourChangeStatusController = self::getContainer()->get(HeliosPESRetourChangeStatusController::class);
        $this->expectException(Exception::class);
        $this->expectOutputRegex("#KO#");
        $heliosPESRetourChangeStatusController->changeStatusAction();
    }
}
