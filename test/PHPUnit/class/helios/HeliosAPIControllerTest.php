<?php

use S2low\Controller\Legacy\HeliosAPIController;
use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Lib\SQLQuery;

class HeliosAPIControllerTest extends S2lowTestCase
{
    use HeliosUtilitiesTestTrait {
        createTransaction as createTransactionTrait;
    }

    private function getHeliosAPIController()
    {
        return $this->getObjectInstancier()->get(HeliosAPIController::class);
    }

    /**
     * @return false|mixed
     * @throws Exception
     */
    private function createTransaction()
    {
        $transaction_id = $this->createTransactionTrait();
        $sql = "UPDATE helios_transactions SET submission_date='2017-07-31T00:00:01' WHERE id=?";
        $this->getSQLQuery()->query($sql, $transaction_id);
        return $transaction_id;
    }

    /**
     * @throws Exception
     */
    public function testNbCreatedPESByAuthoritiesAndMonth()
    {
        $this->createTransaction();

        $this->getObjectInstancier()->get(Environnement::class)->get()->set('month', '7');
        $this->getObjectInstancier()->get(Environnement::class)->get()->set('year', '2017');

        $this->setAdminGroupAuthentication();
        ob_start();
        $this->getHeliosAPIController()->nbCreatedPesAllerByAuthorityGroupIdAndMonthAction();
        $data = ob_get_contents();
        ob_end_clean();
        $this->assertJsonStringEqualsJsonFile(
            __DIR__ . "/fixtures/nbTransactionPerAuthorities.json",
            $data
        );
    }

    /**
     * @throws Exception
     */
    public function testNbCreatedPESByAuthoritiesAndMonthGroupProvided()
    {
        $this->createTransaction();
        $this->getObjectInstancier()->get(Environnement::class)->get()->set('month', '7');
        $this->getObjectInstancier()->get(Environnement::class)->get()->set('year', '2017');
        $this->getObjectInstancier()->get(Environnement::class)->get()->set('authority_group_id', '1');
        $this->setSuperAdminAuthentication();

        ob_start();
        $this->getHeliosAPIController()->nbCreatedPesAllerByAuthorityGroupIdAndMonthAction();
        $data = ob_get_contents();
        ob_end_clean();
        $this->assertJsonStringEqualsJsonFile(
            __DIR__ . "/fixtures/nbTransactionPerAuthorities.json",
            $data
        );
    }

    /**
     * @throws Exception
     */
    public function testNbCreatedPESByAuthoritiesAndMonthNoGroupProvided()
    {

        $this->createTransaction();
        $this->getObjectInstancier()->get(Environnement::class)->get()->set('month', '7');
        $this->getObjectInstancier()->get(Environnement::class)->get()->set('year', '2017');
        $this->getObjectInstancier()->get(Environnement::class)->get()->set('authority_group_id', '1');
        $this->setAdminGroupAuthentication();
        $sql = "UPDATE authorities SET authority_group_id=NULL WHERE authority_group_id=1";
        $this->getObjectInstancier()->get(SQLQuery::class)->query($sql);
        ob_start();
        $this->getHeliosAPIController()->nbCreatedPesAllerByAuthorityGroupIdAndMonthAction();
        $data = ob_get_contents();
        ob_end_clean();
        $this->assertJsonStringEqualsJsonFile(
            __DIR__ . "/fixtures/nbTransactionPerAuthoritiesFailed.json",
            $data
        );
    }
}
