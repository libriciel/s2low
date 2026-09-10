<?php

use IntegrationTests\S2lowIntegrationTestCase;
use PHPUnit\ActesUtilitiesTestTrait;
use S2low\Enum\UserRole;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Controller\AdminController;
use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Lib\RedirectException;
use S2lowLegacy\Model\AuthoritySiretSQL;

class AdminControllerTest extends S2lowIntegrationTestCase
{
    use ActesUtilitiesTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUserWithRole(UserRole::SuperAdministrateur);
    }

    public function testActionBefore()
    {
        $adminController = self::getContainer()->get(AdminController::class);
        $adminController->_actionBefore("Mock", "mock");
        $this->assertTrue(true);
    }

    public function testAuthoritySiretAction()
    {
        $environnement = self::getContainer()->get(Environnement::class);
        $environnement->get()->set('id', 1);

        $adminController = self::getContainer()->get(AdminController::class);
        $adminController->authoritySiretAction();
        $this->assertEquals(1, $adminController->authority_info['id']);
    }

    public function testAuthoritySiretTemplate()
    {
        $environnement = self::getContainer()->get(Environnement::class);
        $environnement->get()->set('id', 1);

        $adminController = self::getContainer()->get(AdminController::class);
        $adminController->_actionBefore("Admin", "authoritySiret");
        $this->expectOutputRegex("#Numéros SIRET - Bourg-en-Bresse#");
        $adminController->authoritySiretAction();
        $adminController->_actionAfter();
    }


    public function testAuthoritySiretActionNoId()
    {
        $adminController = self::getContainer()->get(AdminController::class);
        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage("admin_authorities.php");
        $adminController->authoritySiretAction();
    }

    public function testOtherAuthority()
    {
        $this->setUserWithRole(UserRole::AdministrateurCollectivite);
        $adminController = self::getContainer()->get(AdminController::class);
        $_GET['id'] = 13;
        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage("Redirect to");
        $adminController->authoritySiretAction();
    }

    public function testAddSiretNoValue()
    {
        $adminController = self::getContainer()->get(AdminController::class);
        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage("admin_authorities.php");
        $adminController->authoritySiretAddAction();
    }

    public function testAddSiretBadSiret()
    {
        self::getContainer()->get(Environnement::class)->post()->set('authority_id', 1);
        self::getContainer()->get(Environnement::class)->post()->set('siret', 'badsiret');

        $adminController = self::getContainer()->get(AdminController::class);

        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage("admin_authority_siret.php?id=1&siret=badsiret");
        $adminController->authoritySiretAddAction();
    }

    public function testAddSiret()
    {
        self::getContainer()->get(Environnement::class)->post()->set('authority_id', 1);
        self::getContainer()->get(Environnement::class)->post()->set('siret', '06552185881996');

        $adminController = self::getContainer()->get(AdminController::class);
        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage("admin_authority_siret.php?id=1");
        $adminController->authoritySiretAddAction();
    }

    public function testDelSiret()
    {
     //Migration php 8 : ce test ne fonctionnait pas ...
        self::getContainer()->get(Environnement::class)->post()->set('authority_siret_id', "06552185881996");

        $authoritySiret = self::getContainer()->get(AuthoritySiretSQL::class);
        ;
        $authority_siret_id = $authoritySiret->add(1, "06552185881996");

        self::getContainer()->get(Environnement::class)->post()->set('authority_siret_id', $authority_siret_id);

        $adminController = self::getContainer()->get(AdminController::class);
        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage("admin_authority_siret.php?");
        $adminController->authoritySiretDelAction();
    }

    public function testAddSiretAPI()
    {
        $environnement = self::getContainer()->get(Environnement::class);
        $environnement->post()->set('authority_id', 1);
        $environnement->post()->set('siret', "06552185881996");
        $environnement->post()->set('api', 1);

        $adminController = self::getContainer()->get(AdminController::class);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Exit");
        $this->expectOutputRegex("#Num\\\u00e9ro SIRET ajout\\\u00e9#");
        $adminController->authoritySiretAddAction();
    }

    public function testListSiretApi()
    {
        $environnement = self::getContainer()->get(Environnement::class);
        $environnement->get()->set('api', 1);
        $environnement->get()->set('id', "1");

        $authoritySiret = self::getContainer()->get(AuthoritySiretSQL::class);
        ;
        $authoritySiret->add(1, "12345678900014");

        $adminController = self::getContainer()->get(AdminController::class);

        $this->expectOutputRegex("#12345678900014#");
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("exit() called");
        $adminController->authoritySiretAction();
    }

    public function testAuthoritiesAction()
    {
        $adminController = self::getContainer()->get(AdminController::class);
        $adminController->authoritiesAction();
        $this->assertEquals("Gestion des collectivités", $adminController->getViewParameter('titre'));
    }

    public function testAuthoritiesActionGroupAdmin()
    {
        $this->setUserWithRole(UserRole::AdministrateurGroupe);
        $adminController = self::getContainer()->get(AdminController::class);
        $adminController->authoritiesAction();

        $this->assertEquals("Gestion des collectivités du groupe Groupe de test", $adminController->getViewParameter('titre'));

        $authorities = $adminController->authorities;
        foreach ($authorities as $authority) {
            $this->assertTrue(
                (int)$authority['actes_group_id'] === 1 || (int)$authority['helios_group_id'] === 1
            );
        }
    }

    public function testAuthoritiesActionGroupAdminSeesAnAuthorityAdministeredForActesOnly()
    {
        $this->givenAuthority2IsAdministeredBy(1, 2);
        $this->setUserWithRole(UserRole::AdministrateurGroupe);
        $adminController = self::getContainer()->get(AdminController::class);

        $adminController->authoritiesAction();

        $this->assertContains(2, array_column($adminController->authorities, 'id'));
    }

    public function testAuthoritiesActionGroupAdminDoesNotSeeAnAuthorityItDoesNotAdminister()
    {
        $this->givenAuthority2IsAdministeredBy(2, 2);
        $this->setUserWithRole(UserRole::AdministrateurGroupe);
        $adminController = self::getContainer()->get(AdminController::class);

        $adminController->authoritiesAction();

        $this->assertNotContains(2, array_column($adminController->authorities, 'id'));
    }

    public function testAuthoritiesActionNamesBothAdministeringGroups()
    {
        $this->givenAuthority2IsAdministeredBy(1, 2);
        $adminController = self::getContainer()->get(AdminController::class);

        $adminController->authoritiesAction();

        $authority = $this->authorityFromList($adminController->authorities, 2);
        $this->assertSame('Groupe de test', $authority['actes_group_name']);
        $this->assertSame('second groupe', $authority['helios_group_name']);
    }

    public function testAuthoritiesActionIsRefusedToAnAuthorityAdmin()
    {
        $this->setUserWithRole(UserRole::AdministrateurCollectivite);
        $adminController = self::getContainer()->get(AdminController::class);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Accès refusé");
        $adminController->authoritiesAction();
    }

    private function givenAuthority2IsAdministeredBy(int $actesGroupId, int $heliosGroupId): void
    {
        $this->getSQLQuery()->query(
            'UPDATE authorities SET actes_group_id = ?, helios_group_id = ? WHERE id = 2',
            $actesGroupId,
            $heliosGroupId
        );
    }

    /**
     * @param array<int, array<string, mixed>> $authorities
     * @return array<string, mixed>
     */
    private function authorityFromList(array $authorities, int $authorityId): array
    {
        foreach ($authorities as $authority) {
            if ((int)$authority['id'] === $authorityId) {
                return $authority;
            }
        }

        self::fail("La collectivité $authorityId n'est pas dans la liste");
    }

    public function testAuthoritiesActionAPI()
    {
        self::getContainer()->get(Environnement::class)->get()->set('api', 1);

        $adminController = self::getContainer()->get(AdminController::class);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("exit() called");
        $this->expectOutputRegex("##");
        $adminController->authoritiesAction();
        $out = $this->getActualOutput();
        $result = json_decode($out, true);
        $this->assertEquals(1, $result[1]['id']);
    }

    protected function getActesTransactionsSQL(): ActesTransactionsSQL
    {
        return self::getContainer()->get(ActesTransactionsSQL::class);
    }
}
