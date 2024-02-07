<?php

declare(strict_types=1);

namespace PHPUnit\public_ssl\module\helios;

use DateTime;
use Exception;
use DateInterval;
use PHPUnit\HeliosUtilitiesTestTrait;
use PHPUnit\S2lowTestCase;
use S2lowLegacy\Class\Authority;
use S2lowLegacy\Model\GroupSQL;
use S2lowLegacy\Model\HeliosTransactionsSQL;

class HeliosTransactionTest extends S2lowTestCase
{
    use HeliosUtilitiesTestTrait;

    private HeliosTransactionsSQL $heliosTransactionsSQL;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->heliosTransactionsSQL = $this->getObjectInstancier()->get(HeliosTransactionsSQL::class);
    }

    /**
     * Lorsqu'il n'y a aucune transaction, on a 0 transactions pour un volume de 0
     * @return void
     * @throws \Exception
     */
    public function testCountTransactions()
    {
        $this->assertEquals(
            0,
            $this->heliosTransactionsSQL->countTransactions()
        );

        $this->assertEquals(
            0,
            $this->heliosTransactionsSQL->countTransactionVol()
        );
    }

    /**
     * Pour une seule transaction crée, le nombre et le volume sont corrects
     * @return void
     * @throws \Exception
     */
    public function testCountTransactionsWithOneTransaction()
    {
        $this->createTransaction(1, HeliosTransactionsSQL::POSTE);

        $this->assertEquals(
            1,
            $this->heliosTransactionsSQL->countTransactions()
        );

        $this->assertEquals(
            12345678,
            $this->heliosTransactionsSQL->countTransactionVol("")
        );
    }

    /**
     * Pour une deux transactions crées, le nombre et le volume sont corrects
     * @return void
     * @throws \Exception
     */
    public function testCountTransactionsWithTwoTransactions()
    {
        $this->createTransaction(1, HeliosTransactionsSQL::POSTE);
        $this->createTransaction(1, HeliosTransactionsSQL::POSTE);

        $this->assertEquals(
            2,
            $this->heliosTransactionsSQL->countTransactions()
        );

        $this->assertEquals(
            24691356,
            $this->heliosTransactionsSQL->countTransactionVol("")
        );
    }

    /**
     * Spécifier le statut fonctionne correctement
     * @return void
     * @throws \Exception
     */
    public function testCountTransactionsWithOnePostedTransaction()
    {
        $this->createTransaction(1, HeliosTransactionsSQL::POSTE);

        $this->assertEquals(
            0,
            $this->heliosTransactionsSQL->countTransactions(null, true)
        );

        $this->assertEquals(
            0,
            $this->heliosTransactionsSQL->countTransactionVol("", true)
        );
    }

    /**
     * La spécification du statut transmis fonctionne correctement
     * @return void
     * @throws \Exception
     */
    public function testCountTransactionsWithOneTransmittedTransaction()
    {
        $this->createTransaction(1, HeliosTransactionsSQL::TRANSMIS);

        $this->assertEquals(
            1,
            $this->heliosTransactionsSQL->countTransactions(null, true)
        );

        $this->assertEquals(
            12345678,
            $this->heliosTransactionsSQL->countTransactionVol("", true)
        );
    }

    /**
     * @dataProvider datesProvider
     */
    public function testCountTransactionsWithDates(
        DateTime $date,
        bool $byMonth,
        bool $byYear,
        int $expectedNumber,
        int $expectedVolume
    ): void {
        $this->createTransaction(1, HeliosTransactionsSQL::POSTE, $date->format("Y-m-d H:i:s"));

        $this->assertEquals(
            $expectedNumber,
            $this->heliosTransactionsSQL->countTransactions(null, false, $byMonth, $byYear)
        );

        $this->assertEquals(
            $expectedVolume,
            $this->heliosTransactionsSQL->countTransactionVol("", false, $byMonth, $byYear)
        );
    }

    public function datesProvider()
    {
        return [
            // Une transaction créée avant le début d'année, count byYear => aucune transaction contée
            [(new DateTime(date('Y-01-01 00:00:00')))->sub(DateInterval::createFromDateString("1 day")),false,true,0,0],
            // Une transaction crée cette année, count byYear => une transaction contée
            [(new DateTime(date('Y-01-01 00:00:00')))->add(DateInterval::createFromDateString("3 day")), false, true, 1, 12345678],
            // Une transaction créée avant le début du mois, count byMonth => aucune transaction contée
            [(new DateTime(date('Y-m-01 00:00:00')))->sub(DateInterval::createFromDateString("1 day")), true, false, 0,0],
            // Une transaction créée dans le mois, count byMonth => une transaction contée
            [(new DateTime(date('Y-m-01 00:00:00')))->add(DateInterval::createFromDateString("3 day")), true, false, 1, 12345678]
        ];
    }

    public function testCountTransactionsWithOneTransactionAndGroupAdmin()
    {
        $this->createTransaction(1, HeliosTransactionsSQL::POSTE);

        $author_filter = "authorities.authority_group_id=1";

        $this->assertEquals(
            1,
            $this->heliosTransactionsSQL->countTransactions($author_filter)
        );

        $this->assertEquals(
            12345678,
            $this->heliosTransactionsSQL->countTransactionVol($author_filter)
        );
    }

    public function testCountTransactionsWithMultipleConditions()
    {
        $FirstDayOfYear = new DateTime(date('Y-01-01 00:00:00'));

        $beforeFirstDayOfYear = $FirstDayOfYear->add(DateInterval::createFromDateString("3 day"));

        $this->createTransaction(1, HeliosTransactionsSQL::TRANSMIS, $beforeFirstDayOfYear->format("Y-m-d H:i:s"));

        $author_filter = "authorities.authority_group_id=1";

        $this->assertEquals(
            1,
            $this->heliosTransactionsSQL->countTransactions($author_filter, true, false, true)
        );

        $this->assertEquals(
            12345678,
            $this->heliosTransactionsSQL->countTransactionVol($author_filter, true, false, true)
        );
    }



    public function testCountTransactionsWithOneTransactionAndGroupAdminOutsideAuthority()
    {
        $groupeId = $this->createGroupe();
        $authority_id = $this->createAuthority($groupeId);
        $transactionId = $this->createTransaction($authority_id, HeliosTransactionsSQL::POSTE);

        $author_filter = "authorities.authority_group_id= $groupeId";

        $this->assertEquals(
            1,
            $this->heliosTransactionsSQL->countTransactions($author_filter)
        );

        $this->assertEquals(
            12345678,
            $this->heliosTransactionsSQL->countTransactionVol($author_filter)
        );
    }

    /**
     * @param mixed $groupeId
     * @return mixed|null
     */
    private function createAuthority(mixed $groupeId): mixed
    {
        $authority = new Authority();

        $authority->set("name", "GroupeTestHeliosTransaction");
        $authority->set("siren", 000000);
        $authority->set("authority_group_id", $groupeId);
        $authority->set("agreement", "agreement");
        $authority->set("status", 1);
        $authority->set("authority_type_id", 1);
        $authority->set("department", 22);
        $authority->set("district", "2");
        $authority->set("helios_ftp_dest", "test");

        $authority->set("email", "test@test.Fr");
        $authority->set("default_broadcast_email", "test@test.fr");
        $authority->set("broadcast_email", "test@test.fr");
        $authority->set("address", "Rue Charlemont");
        $authority->set("postal_code", "29620");
        $authority->set("city", "Lanmeur");
        $authority->set("telephone", "telephone");
        $authority->set("fax", "0000");
        $authority->set("email_mail_securise", "email_mail_securise");
        $authority->set("descr_mail_securise", "descr_mail_securise");
        $authority->set("new_notification", false);

        $authority->save(true, false);

        $authority_id = $authority->getId();
        return $authority_id;
    }

    /**
     * @return false|mixed
     */
    private function createGroupe(): mixed
    {
        /** @var GroupSQL $groupSQL */
        $groupSQL = $this->getObjectInstancier()->get(GroupSQL::class);
        $groupeId = $groupSQL->edit(null, "GroupeTestHeliosTransaction", 1);
        return $groupeId;
    }
}
