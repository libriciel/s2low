<?php

use S2lowLegacy\Class\actes\ActesEnvelopeSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Lib\SQLQuery;

class ActesApiControllerTest extends S2lowTestCase
{
    use ActesUtilitiesTestTrait;


    public function testActesStatus()
    {
        $this->setUserAuthentification();
        $this->expectOutputRegex("#En attente de transmission#");
        $this->getActesAPIController()->listStatusAction();
    }

    public function testNbActes()
    {
        $this->setUserAuthentification();
        $this->expectOutputString("{\"status_id\":0,\"authority_id\":1,\"nb_transactions\":0}");
        $this->getActesAPIController()->nbActesAction();
    }

    public function testListActes()
    {
        $this->setUserAuthentification();
        $this->expectOutputString("{\"status_id\":\"0\",\"authority_id\":\"1\",\"offset\":\"0\",\"limit\":\"100\",\"transactions\":[]}");
        $this->getActesAPIController()->listActesAction();
    }

    public function testActionAfter()
    {
        $this->setUserAuthentification();
        $this->expectOutputString("");
        $this->getActesAPIController()->_actionAfter();
    }

    /**
     * @throws Exception
     */
    public function testListDocumentPrefectureAction()
    {
        $this->createRelatedTransaction();
        $this->setUserAuthentification();
        $this->expectOutputRegex('#"number":"20170728C"#');
        $this->getActesAPIController()->listDocumentPrefectureAction();
    }


    /**
     * @return false|mixed
     * @throws Exception
     */
    private function createRelatedTransaction()
    {
        $transaction_id = $this->createTransaction(4);
        $actesTransactionsSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);

        $transaction_info = $actesTransactionsSQL->getInfo($transaction_id);

        $actesEnvelopeSQL = $this->getObjectInstancier()->get(ActesEnvelopeSQL::class);

        $related_envelope_id = $actesEnvelopeSQL->createRelatedEnveloppe($transaction_info['envelope_id'], 'a', 12);

        return $actesTransactionsSQL->createRelatedTransaction($related_envelope_id, 3, '2018-01-01', $transaction_id);
    }

    /**
     * @throws Exception
     */
    public function testActionMarkAsRead()
    {
        $transaction_id = $this->createRelatedTransaction();
        $this->setUserAuthentification();
        $this->expectOutputRegex('#"number":"20170728C".*\{"result":"ok"\}\[\]#');
        $this->getActesAPIController()->listDocumentPrefectureAction();
        $this->getObjectInstancier()->get(Environnement::class)->get()->set('transaction_id', $transaction_id);
        $this->getActesAPIController()->documentPrefectureMarkAsReadAction();
        $this->getActesAPIController()->listDocumentPrefectureAction();
    }

    /**
     * @throws Exception
     */
    public function testNbCreatedActesByAuthoritiesAndMonth()
    {
        $this->createTransaction(1);
        $this->getObjectInstancier()->get(Environnement::class)->get()->set('month', '7');
        $this->getObjectInstancier()->get(Environnement::class)->get()->set('year', '2017');

        $this->setAdminGroupAuthentication();
        ob_start();
        $this->getActesAPIController()->nbCreatedActesByAuthorityGroupIdAndMonthAction();
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
    public function testNbCreatedActesByAuthoritiesAndMonthGroupProvided()
    {
        $this->createTransaction(1);
        $this->getObjectInstancier()->get(Environnement::class)->get()->set('month', '7');
        $this->getObjectInstancier()->get(Environnement::class)->get()->set('year', '2017');
        $this->getObjectInstancier()->get(Environnement::class)->get()->set('authority_group_id', '1');
        $this->setSuperAdminAuthentication();

        ob_start();
        $this->getActesAPIController()->nbCreatedActesByAuthorityGroupIdAndMonthAction();
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
    public function testNbCreatedActesByAuthoritiesAndMonthNoGroupProvided()
    {

        $this->createTransaction(1);
        $this->getObjectInstancier()->get(Environnement::class)->get()->set('month', '7');
        $this->getObjectInstancier()->get(Environnement::class)->get()->set('year', '2017');
        $this->getObjectInstancier()->get(Environnement::class)->get()->set('authority_group_id', '1');
        $this->setAdminGroupAuthentication();
        $sql = "UPDATE authorities SET authority_group_id=NULL WHERE authority_group_id=1";
        $this->getObjectInstancier()->get(SQLQuery::class)->query($sql);
        ob_start();
        $this->getActesAPIController()->nbCreatedActesByAuthorityGroupIdAndMonthAction();
        $data = ob_get_contents();
        ob_end_clean();
        $this->assertJsonStringEqualsJsonFile(
            __DIR__ . "/fixtures/nbTransactionPerAuthoritiesFailed.json",
            $data
        );
    }
}
