<?php

namespace PHPUnit\controller;

use Exception;
use HeliosUtilitiesTestTrait;
use IntegrationTests\S2lowIntegrationTestCase;
use S2low\Enum\UserRole;
use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Lib\SQLQuery;
use S2lowLegacy\Model\HeliosTransactionsSQL;

class HeliosAPIControllerTest extends S2lowIntegrationTestCase
{
    use HeliosUtilitiesTestTrait {
        createTransaction as createTransactionTrait;
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
        $this->setUserWithRole(UserRole::AdministrateurGroupe);
        $this->setUserAuthority(1);
        $this->createTransaction();

        self::getContainer()->get(Environnement::class)->get()->set('month', '7');
        self::getContainer()->get(Environnement::class)->get()->set('year', '2017');

        $this->client->request(
            'GET',
            'modules/helios/api/nb_pes_aller_by_authorities_and_date.php',
        );

        $data = $this->client->getResponse()->getContent();

        $this->assertJsonStringEqualsJsonFile(
            __DIR__ . "/fixtures/nbTransactionPerAuthorities.json",
            $data
        );
        self::assertTrue(true);
    }

    /**
     * @throws Exception
     */
    public function testNbCreatedPESByAuthoritiesAndMonthGroupProvided()
    {
        $this->createTransaction();
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        self::getContainer()->get(Environnement::class)->get()->set('month', '7');
        self::getContainer()->get(Environnement::class)->get()->set('year', '2017');
        self::getContainer()->get(Environnement::class)->get()->set('authority_group_id', '1');

        $this->client->request(
            'GET',
            'modules/helios/api/nb_pes_aller_by_authorities_and_date.php',
        );

        $data = $this->client->getResponse()->getContent();

        $this->assertJsonStringEqualsJsonFile(
            __DIR__ . "/fixtures/nbTransactionPerAuthorities.json",
            $data
        );
        self::assertTrue(true);
    }

    /**
     * @throws Exception
     */
    public function testNbCreatedPESByAuthoritiesAndMonthNoGroupProvided()
    {
        $this->setUserAuthority(1);
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $this->createTransaction();

        self::getContainer()->get(Environnement::class)->get()->set('month', '7');
        self::getContainer()->get(Environnement::class)->get()->set('year', '2017');


        // Le groupe interrogé est celui de l'appelant : c'est le sien qu'il faut retirer.
        $sql = "UPDATE users SET authority_group_id=NULL";
        self::getContainer()->get(SQLQuery::class)->query($sql);

        $this->client->request(
            'GET',
            'modules/helios/api/nb_pes_aller_by_authorities_and_date.php',
        );

        $data = $this->client->getResponse()->getContent();
        $this->assertJsonStringEqualsJsonFile(
            __DIR__ . "/fixtures/nbTransactionPerAuthoritiesFailed.json",
            $data
        );
        self::assertTrue(true);
    }

    public function getHeliosTransactionsSQL(): HeliosTransactionsSQL
    {
        return self::getContainer()->get(HeliosTransactionsSQL::class);
    }
}
