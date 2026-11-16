<?php

declare(strict_types=1);

namespace IntegrationTests;

use HeliosUtilitiesTestTrait;
use PHPUnit\ActesUtilitiesTestTrait;
use S2low\Enum\UserRole;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Model\HeliosTransactionsSQL;

final class ModuleStatisticsTest extends S2lowIntegrationTestCase
{
    use ActesUtilitiesTestTrait, HeliosUtilitiesTestTrait {
        HeliosUtilitiesTestTrait::createTransaction insteadof ActesUtilitiesTestTrait;
    }

    private const int GROUP_OF_THE_ADMIN = 1;
    private const int OTHER_GROUP = 2;
    private const int AUTHORITY_ADMINISTERED_FOR_HELIOS = 2;
    private const int AUTHORITY_OF_ANOTHER_HELIOS_GROUP = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUserWithRole(UserRole::AdministrateurGroupe);
    }

    public function getHeliosTransactionsSQL(): HeliosTransactionsSQL
    {
        return new HeliosTransactionsSQL($this->sqlQuery);
    }

    protected function getActesTransactionsSQL(): ActesTransactionsSQL
    {
        return self::getContainer()->get(ActesTransactionsSQL::class);
    }

    public function testHeliosStatisticsOfAGroupCountTheTransactionsOfAuthoritiesWhoseHeliosGroupItIs(): void
    {
        $this->sqlQuery->query('UPDATE authorities SET helios_group_id = ?', [self::OTHER_GROUP]);
        $this->sqlQuery->query(
            'UPDATE authorities SET helios_group_id = ? WHERE id = ?',
            [self::GROUP_OF_THE_ADMIN, self::AUTHORITY_ADMINISTERED_FOR_HELIOS]
        );
        $this->createTransaction(self::AUTHORITY_ADMINISTERED_FOR_HELIOS, HeliosTransactionsSQL::POSTE);
        $this->createTransaction(self::AUTHORITY_OF_ANOTHER_HELIOS_GROUP, HeliosTransactionsSQL::POSTE);

        $page = $this->client->request('GET', '/modules/helios/helios_stats.php');

        static::assertMatchesRegularExpression(
            '/Nombre de transaction postées sur le tdt\x{00A0}: 1\b/u',
            $page->filterXPath("//dt[contains(., 'En totalité')]/following-sibling::dd[1]")->text()
        );
    }

    public function testActesStatisticsAreRefusedToAGroupThatAdministersActesNowhere(): void
    {
        $this->sqlQuery->query('UPDATE authorities SET actes_group_id = ?', [self::OTHER_GROUP]);

        $this->client->request('GET', '/modules/actes/actes_stats.php');

        static::assertSame('Accès refusé', $_SESSION['error'] ?? null);
        static::assertStringNotContainsString("Nombre d'enveloppes postées", $this->client->getResponse()->getContent());
    }

    public function testHeliosStatisticsAreRefusedToAGroupThatAdministersHeliosNowhere(): void
    {
        $this->sqlQuery->query('UPDATE authorities SET helios_group_id = ?', [self::OTHER_GROUP]);

        $this->client->request('GET', '/modules/helios/helios_stats.php');

        static::assertSame('Accès refusé', $_SESSION['error'] ?? null);
        static::assertStringNotContainsString('Nombre de transaction postées', $this->client->getResponse()->getContent());
    }

    public function testLegacyMenuOffersAGroupAdminOnlyTheStatisticsOfTheModulesItsGroupAdministers(): void
    {
        $this->sqlQuery->query('UPDATE authorities SET actes_group_id = ?', [self::OTHER_GROUP]);

        $this->client->request('GET', '/modules/helios/index.php');

        $content = $this->client->getResponse()->getContent();
        static::assertStringContainsString('Statistiques module helios', $content);
        static::assertStringNotContainsString('Statistiques module actes', $content);
        static::assertStringNotContainsString('Exports module actes', $content);
    }

    public function testCountsTheActesOfTheAuthoritiesWhoseActesGroupIsTheGroup(): void
    {
        $this->sqlQuery->query('UPDATE authorities SET actes_group_id = ? WHERE id = 1', [self::OTHER_GROUP]);
        $this->createTransactionOfType(ActesStatusSQL::STATUS_POSTE);

        $nbActesPerAuthority = $this->getActesTransactionsSQL()->getNbActesByAuthorityGroupIdBeetweenDate(
            self::OTHER_GROUP,
            '2017-07-01',
            '2017-07-31'
        );

        static::assertSame([1 => 1], array_column($nbActesPerAuthority, 'nb_transactions', 'authority_id'));
    }

    public function testNbCreatedPESCountsTheAuthoritiesWhoseHeliosGroupIsTheGroup(): void
    {
        $this->sqlQuery->query('UPDATE authorities SET helios_group_id = ? WHERE id = 1', [self::OTHER_GROUP]);
        $this->createTransaction(1, null, '2017-07-31T00:00:01');
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $environnement = self::getContainer()->get(Environnement::class);
        $environnement->get()->set('month', '7');
        $environnement->get()->set('year', '2017');
        $environnement->get()->set('authority_group_id', (string)self::OTHER_GROUP);

        $this->client->request('GET', 'modules/helios/api/nb_pes_aller_by_authorities_and_date.php');

        $data = json_decode($this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        static::assertSame([1 => 1], array_column($data['nbTransactionPerAuthorities'], 'nb_transactions', 'authority_id'));
    }
}
