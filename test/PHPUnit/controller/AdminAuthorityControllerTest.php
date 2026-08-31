<?php

use IntegrationTests\S2lowIntegrationTestCase;
use S2low\Enum\UserRole;
use S2lowLegacy\Class\actes\ActesConventions;
use S2lowLegacy\Controller\AdminAuthorityController;
use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Lib\ObjectInstancier;
use S2lowLegacy\Lib\RedirectException;
use S2lowLegacy\Lib\SQLQuery;

class AdminAuthorityControllerTest extends S2lowIntegrationTestCase
{
    /**
     * @throws RedirectException
     */
    public function testDownloadConventionAction()
    {
        $actesConvention = $this->getMockBuilder(ActesConventions::class)->disableOriginalConstructor()->getMock();
        $actesConvention->method("getConventionFilepath")->willReturn(
            __DIR__ . "/../class/fixtures/vide.pdf"
        );
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        self::getContainer()->get(Environnement::class)->get()->set('authority_id', 1);
        self::getContainer()->set(ActesConventions::class, $actesConvention);
        $adminAuthorityController = $this->getAdminAuthorityController();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('exit() called');
        $this->expectOutputRegex("##");
        $adminAuthorityController->downloadConventionAction();
    }


    /**
     * @throws RedirectException
     */
    public function testDownloadConventionActionNoConvention()
    {
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        self::getContainer()->get(Environnement::class)->get()->set('authority_id', 1);
        $adminAuthorityController = $this->getAdminAuthorityController();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Redirect to /admin/authorities/admin_authority_edit.php?id=1 with message : Impossible de récupérer la convention');

        $adminAuthorityController->downloadConventionAction();
    }

    /**
     * @throws RedirectException
     */
    public function testDownloadConventionActionNoAuthorityId()
    {
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $adminAuthorityController = self::getContainer()->get(AdminAuthorityController::class);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Redirect to');

        $adminAuthorityController->downloadConventionAction();
    }

    /**
     * Le cas que verifAdmin() interdisait : le groupe Actes de la collectivité n'est pas son
     * groupe historique, l'administrateur du groupe Actes doit tout de même pouvoir télécharger.
     *
     * @throws RedirectException
     */
    public function testTheActesGroupAdminDownloadsTheConvention()
    {
        $this->givenAConventionExists();
        $this->setUserWithRole(UserRole::AdministrateurGroupe);
        $this->givenAuthority1(['authority_group_id' => 2, 'actes_group_id' => 1]);
        self::getContainer()->get(Environnement::class)->get()->set('authority_id', 1);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('exit() called');
        $this->expectOutputRegex("##");
        $this->getAdminAuthorityController()->downloadConventionAction();
    }

    /**
     * @throws RedirectException
     */
    public function testTheAdminOfAnotherGroupDoesNotDownloadTheConvention()
    {
        $this->givenAConventionExists();
        $this->setUserWithRole(UserRole::AdministrateurGroupe);
        $this->givenAuthority1(['authority_group_id' => 1, 'actes_group_id' => 2]);
        self::getContainer()->get(Environnement::class)->get()->set('authority_id', 1);

        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage('Accès refusé');
        $this->getAdminAuthorityController()->downloadConventionAction();
    }

    /**
     * @throws RedirectException
     */
    public function testTheAuthorityAdminDownloadsItsOwnConvention()
    {
        $this->givenAConventionExists();
        $this->setUserWithRole(UserRole::AdministrateurCollectivite);
        $this->givenAuthority1(['authority_group_id' => 1, 'actes_group_id' => 2]);
        self::getContainer()->get(Environnement::class)->get()->set('authority_id', 1);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('exit() called');
        $this->expectOutputRegex("##");
        $this->getAdminAuthorityController()->downloadConventionAction();
    }

    /**
     * @throws RedirectException
     */
    public function testASimpleUserDoesNotDownloadTheConvention()
    {
        $this->givenAConventionExists();
        $this->setUserWithRole(UserRole::Utilisateur);
        $this->givenAuthority1(['authority_group_id' => 1, 'actes_group_id' => 1]);
        self::getContainer()->get(Environnement::class)->get()->set('authority_id', 1);

        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage('Accès refusé');
        $this->getAdminAuthorityController()->downloadConventionAction();
    }

    private function givenAConventionExists(): void
    {
        $actesConvention = $this->getMockBuilder(ActesConventions::class)->disableOriginalConstructor()->getMock();
        $actesConvention->method("getConventionFilepath")->willReturn(
            __DIR__ . "/../class/fixtures/vide.pdf"
        );
        self::getContainer()->set(ActesConventions::class, $actesConvention);
    }

    private function givenAuthority1(array $groups): void
    {
        self::getContainer()->get(SQLQuery::class)->query(
            'UPDATE authorities SET authority_group_id = ?, actes_group_id = ? WHERE id = 1',
            [$groups['authority_group_id'], $groups['actes_group_id']]
        );
    }

    /**
     * @throws RedirectException
     */
    public function testExportListAction()
    {
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $adminAuthorityController = $this->getAdminAuthorityController();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('exit() called');
        $this->expectOutputRegex("#Bourg-en-Bresse#");
        $adminAuthorityController->exportListAction();
    }

    /**
     * @throws RedirectException
     */
    public function testExportListActionGroupAdmin()
    {
        $this->setUserWithRole(UserRole::AdministrateurGroupe);
        $adminAuthorityController = $this->getAdminAuthorityController();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('exit() called');
        $this->expectOutputRegex("#Bourg-en-Bresse#");
        $adminAuthorityController->exportListAction();
    }

    /**
     * @throws RedirectException
     */
    public function testExportListActionUser()
    {
        $this->setUserWithRole(UserRole::AdministrateurCollectivite);
        $adminAuthorityController = $this->getAdminAuthorityController();
        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage('Vous devez être administrateur de groupe ou super admin');
        $adminAuthorityController->exportListAction();
    }

    private function getAdminAuthorityController(): AdminAuthorityController
    {
        return new AdminAuthorityController(
            self::getContainer()->get(ObjectInstancier::class),
        );
    }
}
