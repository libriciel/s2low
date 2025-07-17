<?php

use IntegrationTests\S2lowIntegrationTestCase;
use S2low\Enum\UserRole;
use S2lowLegacy\Controller\AdminUtilitiesCertificateController;
use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Lib\RedirectException;

class AdminUtilitiesCertificateControllerTest extends S2lowIntegrationTestCase
{
    /**
     * @throws Exception
     */
    public function testTestAction()
    {
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $adminUtilitiesCertificateController = self::getContainer()->get(AdminUtilitiesCertificateController::class);
        $adminUtilitiesCertificateController->testAction();
        $this->assertEmpty($adminUtilitiesCertificateController->getViewParameter('certificate_info'));
    }

    /**
     * @throws Exception
     */
    public function testTestActionSession()
    {
        $certificate_info = ['foo' => 'bar'];
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $adminUtilitiesCertificateController = self::getContainer()->get(AdminUtilitiesCertificateController::class);
        self::getContainer()->get(Environnement::class)->session()->set(AdminUtilitiesCertificateController::SESSION_KEY, $certificate_info);
        $adminUtilitiesCertificateController->testAction();
        $this->assertEquals($certificate_info, $adminUtilitiesCertificateController->getViewParameter('certificate_info'));
    }

    /**
     * @throws RedirectException
     */
    public function testDoAction()
    {
        $environnement = self::getContainer()->get(Environnement::class);
        $adminUtilitiesCertificateController = self::getContainer()->get(AdminUtilitiesCertificateController::class);
        $adminUtilitiesCertificateController->setFiles([
            'certificat' => [
                'tmp_name' => __DIR__ . '/fixtures/contact@example.org.pem',
                'tmp_chaine' => __DIR__ . '/fixtures/ca_users_chaine.pem'
            ]
        ]);

        try {
            $adminUtilitiesCertificateController->doTestAction();
        } catch (RedirectException $e) {
        }

        $result = $environnement->session()->get(AdminUtilitiesCertificateController::SESSION_KEY);
        $this->assertEquals('/C=FR/ST=23 - Creuse/L=Aubusson/O=Libriciel SCOP/OU=tests unitaires s2low/CN=testUnitaires s2low - tests unitaires s2low/emailAddress=test@libriciel.coop', $result['certificate_info']['name']);
        $this->assertEquals(0, $result['nb_users']);
    }

    /**
     * @throws RedirectException
     */
    public function testDoActionNoFile()
    {
        $adminUtilitiesCertificateController = self::getContainer()->get(AdminUtilitiesCertificateController::class);
        $adminUtilitiesCertificateController->setFiles([]);
        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage("Il faut fournir un fichier");
        $adminUtilitiesCertificateController->doTestAction();
    }

    /**
     * @throws RedirectException
     */
    public function testDoActionNoCertificate()
    {
        $adminUtilitiesCertificateController = self::getContainer()->get(AdminUtilitiesCertificateController::class);
        $adminUtilitiesCertificateController->setFiles([
            'certificat' => [
                'tmp_name' => __DIR__ . '/fixtures/pes_aller.xml'
            ]
        ]);
        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage("Impossible de lire le certificat");
        $adminUtilitiesCertificateController->doTestAction();
    }

    /**
     * @throws RedirectException
     */
    public function testDoActionNoUsers()
    {
        $adminUtilitiesCertificateController = self::getContainer()->get(AdminUtilitiesCertificateController::class);
        $adminUtilitiesCertificateController->setFiles([
            'certificat' => [
                'tmp_name' => __DIR__ . '/fixtures/contactSansUser@example.org.pem',
                'tmp_chaine' => __DIR__ . '/fixtures/ca_users_chaine.pem'
            ]
        ]);
        try {
            $adminUtilitiesCertificateController->doTestAction();
        } catch (Exception $e) {
        }
        $session_info = self::getContainer()->get(Environnement::class)->session()->get(AdminUtilitiesCertificateController::SESSION_KEY);
        $this->assertEquals(0, $session_info['nb_users']);
    }
}
