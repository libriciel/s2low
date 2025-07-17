<?php

use IntegrationTests\S2lowIntegrationTestCase;
use S2low\Enum\UserRole;
use S2lowLegacy\Class\ServiceUser;
use S2lowLegacy\Controller\AdminServiceController;
use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Lib\RedirectException;
use S2lowLegacy\Model\ServiceUserSQL;

class AdminServiceControllerTest extends S2lowIntegrationTestCase
{
    private const NOM_SERVICE = 'mon service';

    /** @var AdminServiceController */
    private $adminServiceController;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $this->adminServiceController = self::getContainer()->get(AdminServiceController::class);
    }

    private function createService($name = self::NOM_SERVICE): int
    {
        $serviceUserSQL = self::getContainer()->get(ServiceUserSQL::class);
        return $serviceUserSQL->add($name, 1);
    }

    /**
     * @throws RedirectException
     */
    public function testAddFailed()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Le nom du service est obligatoire");
        $this->adminServiceController->addAction();
    }

    /**
     * @throws RedirectException
     */
    public function testAdd()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Exit !');
        $this->expectOutputRegex('#Le service a #');
        $this->addService();
    }

    /**
     * @throws RedirectException
     */
    public function testAlreadyExists()
    {
        $this->createService();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Exit !');
        $this->expectOutputRegex('#Ce service existe#');
        $this->addService();
    }

    /**
     * @throws RedirectException
     */
    private function addService()
    {
        self::getContainer()->get(Environnement::class)->post()->set('name', self::NOM_SERVICE);
        self::getContainer()->get(Environnement::class)->post()->set('authority_id', 1);
        self::getContainer()->get(Environnement::class)->post()->set('api', 1);
        $this->adminServiceController->addAction();
    }

    public function testListService()
    {
        $this->createService();
        self::getContainer()->get(Environnement::class)->get()->set('authority_id', 1);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("exit() called");
        $this->expectOutputRegex("#\"name\":\"mon service\"#");
        $this->adminServiceController->listAction();
    }

    public function testAddUserAction()
    {
        $service_id = $this->createService();
        self::getContainer()->get(Environnement::class)->post()->set('id_user', 13);
        self::getContainer()->get(Environnement::class)->post()->set('id_service', $service_id);
        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage("Redirect to");
        $this->adminServiceController->addUserAction();
    }

    /**
     * @throws RedirectException
     */
    public function testDetail()
    {
        $service_id = $this->createService();
        self::getContainer()->get(Environnement::class)->get()->set('id', $service_id);
        $this->assertTrue($this->adminServiceController->detailAction());
    }

    public function testDetailWhenNoServiceIdProvided()
    {
        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage("Redirect to /admin/services/admin_services.php with message :");
        $this->adminServiceController->detailAction();
    }

    public function testDetailWhenNoServiceIdDitNotExist()
    {
        self::getContainer()->get(Environnement::class)->get()->set('id', 42);
        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage("Redirect to /admin/services/admin_services.php with message :");
        $this->adminServiceController->detailAction();
    }

    public function testAddParent()
    {
        $parent_id = $this->createService('parent');
        $enfant_id = $this->createService('enfant');
        self::getContainer()->get(Environnement::class)->post()->set('id', $parent_id);
        self::getContainer()->get(Environnement::class)->post()->set('service_id', $enfant_id);
        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage("Parent modifié");
        $this->adminServiceController->addParentAction();
    }

    public function testEnleverUtilisateur()
    {
        $service_id = $this->createService();
        $serviceUser = self::getContainer()->get(ServiceUser::class);
        $serviceUser->addUser(1, $service_id);

        self::getContainer()->get(Environnement::class)->post()->set('id_user', [1]);
        self::getContainer()->get(Environnement::class)->post()->set('id_service', $service_id);
        try {
            $this->adminServiceController->enleverUtilisateurAction();
            $this->assertFalse(true);
        } catch (RedirectException $e) {
            $this->assertEquals(
                "Redirect to /admin/services/gestion-service-content.php?id=$service_id with message : L'utilisateur a été retiré du service",
                $e->getMessage()
            );
        }
        $this->assertEmpty($serviceUser->getListUser($service_id));
    }

    public function testEnleverUtilisateurWhenNoUserIdProvided()
    {
        $service_id = $this->createService();
        self::getContainer()->get(Environnement::class)->post()->set('id_service', $service_id);
        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage("Il faut sélectionner un utilisateur à enlever du service");
        $this->adminServiceController->enleverUtilisateurAction();
    }

    public function testSupprimerService()
    {
        $serviceUserSQL = self::getContainer()->get(ServiceUserSQL::class);
        $service_id = $this->createService();
        $this->assertNotEmpty($serviceUserSQL->getInfo($service_id));
        try {
            self::getContainer()->get(Environnement::class)->post()->set('id', $service_id);
            $this->adminServiceController->supprimerServiceAction();
            $this->assertFalse(true);
        } catch (RedirectException $e) {
            $this->assertEquals(
                "Redirect to /admin/services/admin_services.php with message : Le service a été supprimé",
                $e->getMessage()
            );
        }
        $this->assertEmpty($serviceUserSQL->getInfo($service_id));
    }
}
