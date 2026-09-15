<?php

declare(strict_types=1);

namespace PHPUnit\class\actes;

use Exception;
use IntegrationTests\S2lowIntegrationTestCase;
use PHPUnit\ActesUtilitiesTestTrait;
use S2low\Enum\UserRole;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\TypeTransaction;
use S2lowLegacy\Controller\ActesAPIController;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Lib\ObjectInstancier;
use S2lowLegacy\Lib\SQLQuery;
use S2lowTestCase;

class ActesApiControllerTest extends S2lowIntegrationTestCase
{
    use ActesUtilitiesTestTrait;


    private function getActesAPIController(): ActesAPIController
    {
        return self::getContainer()->get(ActesAPIController::class);
    }

    public function testActesStatus(): void
    {
        $this->setUserWithRole(UserRole::Utilisateur);
        $this->expectOutputRegex('#En attente de transmission#');
        $this->getActesAPIController()->listStatusAction();
    }

    public function testNbActes(): void
    {
        $this->setUserWithRole(UserRole::Utilisateur);
        $this->expectOutputString("{\"status_id\":0,\"authority_id\":1,\"nb_transactions\":0}");
        $this->getActesAPIController()->nbActesAction();
    }

    public function testListActes(): void
    {
        $this->setUserWithRole(UserRole::Utilisateur);
        $this->expectOutputString(
            $this->emptyResponse(0)
        );
        $this->getActesAPIController()->listActesAction();
    }

    /**
     * @throws \Exception
     */
    public function testListActesWithActe(): void
    {
        $transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_POSTE);
        $this->setUserWithRole(UserRole::Utilisateur);
        $this->getEnvironment()->get()->set('status_id', ActesStatusSQL::STATUS_POSTE);

        $this->getActesAPIController()->listActesAction();

        static::assertStringContainsString(
            '{"status_id":"1","authority_id":"1","offset":"0","limit":"100","transactions":[{"id":"' . $transaction_id,
            $this->getActualOutputForAssertion()
        );
    }

    /**
     * @dataProvider maxDatesAndStatusProvider
     * @throws \Exception
     */
    public function testListActesWithActeWithMaxDateMinDateAndStatus(
        ?string $minDate,
        ?string $maxDate,
        int $status,
        string $string
    ): void {
        $id = $this->createTransaction(ActesStatusSQL::STATUS_POSTE);
        $this->updateStatus($id, ActesStatusSQL::STATUS_TRANSMIS, 'message', '2017-08-01');
        $this->setUserWithRole(UserRole::Utilisateur);
        $this->getEnvironment()->get()->set('status_id', $status);
        $this->getEnvironment()->get()->set('min_date', $minDate);
        $this->getEnvironment()->get()->set('max_date', $maxDate);
        $this->getActesAPIController()->listActesAction();

        static::assertStringContainsString(
            $string,
            $this->getActualOutputForAssertion()
        );
    }

    public function maxDatesAndStatusProvider(): iterable
    {
        // La transaction est postée le 2017-07-01 et transmise le 2017-08-01.
        // min_date et max_date encadrent la date de soumission (le passage au statut « posté »),
        // pas la date de passage au statut demandé.

        // si min_date est antérieure à la date de soumission, la transaction apparaitra dans la liste
        yield [
            '2017-06-30',null, ActesStatusSQL::STATUS_TRANSMIS,
            $this->responseWithTransaction(ActesStatusSQL::STATUS_TRANSMIS)];
        // si min_date est null, elle apparaitra dans la liste
        yield [null,null, ActesStatusSQL::STATUS_TRANSMIS,
            $this->responseWithTransaction(ActesStatusSQL::STATUS_TRANSMIS)];
        // si min_date est égale à la date de soumission, elle apparaitra dans la liste
        yield ['2017-07-01',null, ActesStatusSQL::STATUS_TRANSMIS,
            $this->responseWithTransaction(ActesStatusSQL::STATUS_TRANSMIS)];
        // si min_date est postérieure à la date de soumission, on ne verra aucune transaction,
        // même si elle est antérieure à la date de transmission
        yield ['2017-07-02',null, ActesStatusSQL::STATUS_TRANSMIS,
            $this->emptyResponse(ActesStatusSQL::STATUS_TRANSMIS)];
        yield ['2017-08-02',null, ActesStatusSQL::STATUS_TRANSMIS,
            $this->emptyResponse(ActesStatusSQL::STATUS_TRANSMIS)];

        // si max_date est antérieure à la date de soumission, aucune transaction n'apparaitra dans la liste
        yield [null, '2017-06-30', ActesStatusSQL::STATUS_TRANSMIS,
            $this->emptyResponse(ActesStatusSQL::STATUS_TRANSMIS)];
        // si max_date est null, la transaction apparaitra
        yield [null, null, ActesStatusSQL::STATUS_TRANSMIS,
            $this->responseWithTransaction(ActesStatusSQL::STATUS_TRANSMIS)];
        // si max_date est égale à la date de soumission, la transaction apparaitra
        yield [null, '2017-07-01', ActesStatusSQL::STATUS_TRANSMIS,
            $this->responseWithTransaction(ActesStatusSQL::STATUS_TRANSMIS)];
        // si max_date est postérieure à la date de soumission mais antérieure à la date de transmission,
        // la transaction apparaitra tout de même
        yield [null, '2017-07-02', ActesStatusSQL::STATUS_TRANSMIS,
            $this->responseWithTransaction(ActesStatusSQL::STATUS_TRANSMIS)];
        yield [null, '2017-08-02',ActesStatusSQL::STATUS_TRANSMIS,
            $this->responseWithTransaction(ActesStatusSQL::STATUS_TRANSMIS)];

        // la transaction n'est plus au statut « posté », elle n'apparait dans aucune liste de ce statut
        yield [null, '2017-06-30',ActesStatusSQL::STATUS_POSTE,
            $this->emptyResponse(ActesStatusSQL::STATUS_POSTE)];
        yield [null, '2017-07-02', ActesStatusSQL::STATUS_POSTE,
            $this->emptyResponse(ActesStatusSQL::STATUS_POSTE)];
        yield [null, '2017-08-02', ActesStatusSQL::STATUS_POSTE,
            $this->emptyResponse(ActesStatusSQL::STATUS_POSTE)];

        // Si max_date est antérieure à min_date, la liste est vide ...
        yield ['2017-08-02','2017-07-02' , ActesStatusSQL::STATUS_TRANSMIS,
            $this->emptyResponse(ActesStatusSQL::STATUS_TRANSMIS)];
    }

    /**
     * @dataProvider typeProvider
     * @throws \Exception
     */
    public function testListActesWithType(
        int $type,
        int $status,
        string $string
    ): void {
        $id = $this->createTransaction(ActesStatusSQL::STATUS_POSTE);
        $this->updateStatus($id, ActesStatusSQL::STATUS_TRANSMIS, 'message', '2017-08-01');
        $this->setUserWithRole(UserRole::Utilisateur);
        $this->getEnvironment()->get()->set('status_id', $status);
        $this->getEnvironment()->get()->set('type_acte', $type);
        $this->getActesAPIController()->listActesAction();

        static::assertStringContainsString(
            $string,
            $this->getActualOutputForAssertion()
        );
    }

    public function typeProvider(): iterable
    {
        // Une transaction est crée avec le type TransmissionActes
        yield [
            TypeTransaction::TransmissionActe->value, ActesStatusSQL::STATUS_TRANSMIS,
            $this->responseWithTransaction(ActesStatusSQL::STATUS_TRANSMIS)];
        //Il n'y a aucune transaction de type Annulation
        yield [
            TypeTransaction::Annulation->value, ActesStatusSQL::STATUS_TRANSMIS,
            $this->emptyResponse(ActesStatusSQL::STATUS_TRANSMIS)];
    }

    public function testListActesWithWrongType(): void
    {
        $id = $this->createTransaction(ActesStatusSQL::STATUS_POSTE);
        $this->updateStatus($id, ActesStatusSQL::STATUS_TRANSMIS, 'message', '2017-08-01');
        $this->setUserWithRole(UserRole::Utilisateur);
        $this->getEnvironment()->get()->set('status_id', ActesStatusSQL::STATUS_TRANSMIS);
        $this->getEnvironment()->get()->set('type_acte', 99);
        $this->getActesAPIController()->listActesAction();

        static::assertStringContainsString(
            '{"error":"Code 99 invalide, les valeurs possibles sont : ',
            $this->getActualOutputForAssertion()
        );
    }

    public function testActionAfter()
    {
        $this->setUserWithRole(UserRole::Utilisateur);
        $this->expectOutputString('');
        $this->getActesAPIController()->_actionAfter();
    }

    /**
     * @throws Exception
     */
    public function testListDocumentPrefectureAction()
    {
        $this->createRelatedTransaction();
        $this->setUserWithRole(UserRole::Utilisateur);
        $this->expectOutputRegex('#"number":"20170728C"#');
        $this->getActesAPIController()->listDocumentPrefectureAction();
    }


    /**
     * @throws Exception
     */
    public function testActionMarkAsRead(): void
    {
        $transaction_id = $this->createRelatedTransaction();
        $this->setUserWithRole(UserRole::Utilisateur);
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
        $this->setUserWithRole(UserRole::AdministrateurGroupe);
        $this->createTransaction(ActesStatusSQL::STATUS_POSTE);
        $this->getEnvironment()->get()->set('month', '7');
        $this->getEnvironment()->get()->set('year', '2017');


        $this->client->request(
            'GET',
            'modules/actes/api/nb_actes_by_authorities_and_date.php',
        );

        $data = $this->client->getResponse()->getContent();
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
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $this->createTransaction(ActesStatusSQL::STATUS_POSTE);

        $this->getEnvironment()->get()->set('month', '7');
        $this->getEnvironment()->get()->set('year', '2017');
        $this->getEnvironment()->get()->set('authority_group_id', '1');

        $this->client->request(
            'GET',
            'modules/actes/api/nb_actes_by_authorities_and_date.php',
        );
        $data = $this->client->getResponse()->getContent();


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
        $this->createTransaction(ActesStatusSQL::STATUS_POSTE);
        $this->setUserWithRole(UserRole::AdministrateurGroupe);
        $sql = 'UPDATE authorities SET authority_group_id=NULL WHERE authority_group_id=1';
        self::getContainer()->get(SQLQuery::class)->query($sql);

        $this->getEnvironment()->get()->set('month', '7');
        $this->getEnvironment()->get()->set('year', '2017');

        $this->client->request(
            'GET',
            'modules/actes/api/nb_actes_by_authorities_and_date.php',
        );
        $data = $this->client->getResponse()->getContent();

        static::assertJsonStringEqualsJsonFile(
            __DIR__ . '/fixtures/nbTransactionPerAuthoritiesFailed.json',
            $data
        );
    }
    private function getEnvironment(): Environnement
    {
        return self::getContainer()->get(Environnement::class);
    }

    private function emptyResponse(int $status): string
    {
        return '{"status_id":"' . $status . '","authority_id":"1","offset":"0","limit":"100","transactions":[]}';
    }

    private function responseWithTransaction(int $status): string
    {
        return '{"status_id":"' . $status . '","authority_id":"1","offset":"0","limit":"100","transactions":[{';
    }

    public function getActesTransactionsSQL(): ActesTransactionsSQL
    {
        return self::getContainer()->get(ActesTransactionsSQL::class);
    }
}
