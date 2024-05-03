<?php

declare(strict_types=1);

namespace PHPUnit\class\actes;

use Exception;
use PHPUnit\ActesUtilitiesTestTrait;
use S2lowLegacy\Class\actes\ActesEnvelopeSQL;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Lib\SQLQuery;
use S2lowTestCase;

class ActesApiControllerTest extends S2lowTestCase
{
    use ActesUtilitiesTestTrait;


    public function testActesStatus(): void
    {
        $this->setUserAuthentification();
        $this->expectOutputRegex('#En attente de transmission#');
        $this->getActesAPIController()->listStatusAction();
    }

    public function testNbActes(): void
    {
        $this->setUserAuthentification();
        $this->expectOutputString("{\"status_id\":0,\"authority_id\":1,\"nb_transactions\":0}");
        $this->getActesAPIController()->nbActesAction();
    }

    public function testListActes(): void
    {
        $this->setUserAuthentification();
        $this->expectOutputString(
            '{"status_id":"0","authority_id":"1","offset":"0","limit":"100","transactions":[]}'
        );
        $this->getActesAPIController()->listActesAction();
    }

    /**
     * @throws \Exception
     */
    public function testListActesWithActe(): void
    {
        $transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_POSTE);
        $this->setUserAuthentification();
        $this->getEnvironment()->get()->set('status_id', ActesStatusSQL::STATUS_POSTE);

        $this->getActesAPIController()->listActesAction();

        static::assertStringContainsString(
            '{"status_id":"1","authority_id":"1","offset":"0","limit":"100","transactions":[{"id":"' . $transaction_id,
            $this->getActualOutputForAssertion()
        );
    }

    /**
     * @dataProvider minDatesProvider
     * @throws \Exception
     */
    public function testListActesWithActeWithMinDate($date, $string): void
    {
        $this->createTransaction(ActesStatusSQL::STATUS_POSTE);
        $this->setUserAuthentification();
        $this->getEnvironment()->get()->set('status_id', ActesStatusSQL::STATUS_POSTE);
        $this->getEnvironment()->get()->set('min_submission_date', $date);
        $this->getActesAPIController()->listActesAction();

        static::assertStringContainsString(
            $string,
            $this->getActualOutputForAssertion()
        );
    }
    public function minDatesProvider(): iterable
    {
        // la transaction est crée avec une decision_date au 2017-07-01
        // si min_submission_date est antérieure, cette transaction apparaitra dans la liste
        yield ['2017-06-30','{"status_id":"1","authority_id":"1","offset":"0","limit":"100","transactions":[{'];
        // si elle est postérieure, on ne verra aucune transaction
        yield ['2017-07-02','{"status_id":"1","authority_id":"1","offset":"0","limit":"100","transactions":[]}'];
    }

    /**
     * @dataProvider maxDatesProvider
     * @throws \Exception
     */
    public function testListActesWithActeWithMaxDate($date, $string): void
    {
        $this->createTransaction(ActesStatusSQL::STATUS_POSTE);
        $this->setUserAuthentification();
        $this->getEnvironment()->get()->set('status_id', ActesStatusSQL::STATUS_POSTE);
        $this->getEnvironment()->get()->set('max_submission_date', $date);
        $this->getActesAPIController()->listActesAction();

        static::assertStringContainsString(
            $string,
            $this->getActualOutputForAssertion()
        );
    }
    public function maxDatesProvider(): iterable
    {
        // la transaction est crée avec une decision_date au 2017-07-01
        // si max_submission_date est antérieure, aucune transaction n'apparaitra dans la liste
        yield ['2017-06-30','{"status_id":"1","authority_id":"1","offset":"0","limit":"100","transactions":[]}'];
        // si max_submission_date est postérieure, la transaction apparaitra
        yield ['2017-07-02','{"status_id":"1","authority_id":"1","offset":"0","limit":"100","transactions":[{'];
    }
    public function testActionAfter()
    {
        $this->setUserAuthentification();
        $this->expectOutputString('');
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
     * @return int
     * @throws \Exception
     */
    private function createRelatedTransaction(): int
    {
        $transaction_id = $this->createTransaction(4);
        /** @var ActesTransactionsSQL $actesTransactionsSQL */
        $actesTransactionsSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);

        $transaction_info = $actesTransactionsSQL->getInfo($transaction_id);
        /** @var ActesEnvelopeSQL $actesEnvelopeSQL */
        $actesEnvelopeSQL = $this->getObjectInstancier()->get(ActesEnvelopeSQL::class);

        $related_envelope_id = $actesEnvelopeSQL->createRelatedEnveloppe($transaction_info['envelope_id'], 'a', 12);

        return $actesTransactionsSQL->createRelatedTransaction($related_envelope_id, 3, '2018-01-01', $transaction_id);
    }

    /**
     * @throws Exception
     */
    public function testActionMarkAsRead(): void
    {
        $transaction_id = $this->createRelatedTransaction();
        $this->setUserAuthentification();
        $this->expectOutputRegex('#"number":"20170728C".*\{"result":"ok"\}\[\]#');
        $this->getActesAPIController()->listDocumentPrefectureAction();
        $this->getEnvironment()->get()->set('transaction_id', $transaction_id);
        $this->getActesAPIController()->documentPrefectureMarkAsReadAction();
        $this->getActesAPIController()->listDocumentPrefectureAction();
    }

    /**
     * @throws Exception
     */
    public function testNbCreatedActesByAuthoritiesAndMonth(): void
    {
        $this->createTransaction(1);
        $this->getEnvironment()->get()->set('month', '7');
        $this->getEnvironment()->get()->set('year', '2017');

        $this->setAdminGroupAuthentication();
        ob_start();
        $this->getActesAPIController()->nbCreatedActesByAuthorityGroupIdAndMonthAction();
        $data = ob_get_contents();
        ob_end_clean();
        static::assertJsonStringEqualsJsonFile(
            __DIR__ . '/fixtures/nbTransactionPerAuthorities.json',
            $data
        );
    }

    /**
     * @throws Exception
     */
    public function testNbCreatedActesByAuthoritiesAndMonthGroupProvided(): void
    {
        $this->createTransaction(1);
        $this->getEnvironment()->get()->set('month', '7');
        $this->getEnvironment()->get()->set('year', '2017');
        $this->getEnvironment()->get()->set('authority_group_id', '1');
        $this->setSuperAdminAuthentication();

        ob_start();
        $this->getActesAPIController()->nbCreatedActesByAuthorityGroupIdAndMonthAction();
        $data = ob_get_contents();
        ob_end_clean();
        static::assertJsonStringEqualsJsonFile(
            __DIR__ . '/fixtures/nbTransactionPerAuthorities.json',
            $data
        );
    }

    /**
     * @throws Exception
     */
    public function testNbCreatedActesByAuthoritiesAndMonthNoGroupProvided(): void
    {

        $this->createTransaction(1);
        $this->getEnvironment()->get()->set('month', '7');
        $this->getEnvironment()->get()->set('year', '2017');
        $this->getEnvironment()->get()->set('authority_group_id', '1');
        $this->setAdminGroupAuthentication();
        $sql = 'UPDATE authorities SET authority_group_id=NULL WHERE authority_group_id=1';
        $this->getObjectInstancier()->get(SQLQuery::class)->query($sql);
        ob_start();
        $this->getActesAPIController()->nbCreatedActesByAuthorityGroupIdAndMonthAction();
        $data = ob_get_contents();
        ob_end_clean();
        static::assertJsonStringEqualsJsonFile(
            __DIR__ . '/fixtures/nbTransactionPerAuthoritiesFailed.json',
            $data
        );
    }
    private function getEnvironment(): Environnement
    {
        return $this->getObjectInstancier()->get(Environnement::class);
    }
}
