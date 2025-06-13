<?php

use IntegrationTests\S2lowIntegrationTestCase;
use PHPUnit\ActesUtilitiesTestTrait;
use S2low\Enum\UserRole;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\Authentification;
use S2lowLegacy\Class\Database;
use S2lowLegacy\Class\RgsConnexion;
use S2lowLegacy\Controller\ActesPostWithoutSignatureController;
use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Lib\RedirectException;
use S2lowLegacy\Lib\SessionWrapper;

class ActesPostWithoutSignatureControllerTest extends S2lowIntegrationTestCase
{
    use ActesUtilitiesTestTrait;

    /**
     * @throws Exception
     */
    public function testPost()
    {
        $transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_EN_ATTENTE_D_ETRE_SIGNEE);
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        $actesPostWithoutSignature = $this->getObjectInstancier()->get(ActesPostWithoutSignatureController::class);
        $this->getObjectInstancier()->get(Environnement::class)->post()->set('id', $transaction_id);
        try {
            $actesPostWithoutSignature->postAction();
        } catch (Exception $e) {
            $this->assertMatchesRegularExpression("#La transaction $transaction_id a été posté sans signature#", $e->getMessage());
        }

        $actesTransactionsSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);

        $info = $actesTransactionsSQL->getInfo($transaction_id);
        $this->assertEquals(ActesStatusSQL::STATUS_POSTE, $info['last_status_id']);
    }

    /**
     * @throws RedirectException
     */
    public function testPostNoTransactionId()
    {
        $this->setUserWithRole(UserRole::Utilisateur);
        $actesPostWithoutSignature = $this->getObjectInstancier()->get(ActesPostWithoutSignatureController::class);
        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage("Aucun identifiant de transaction trouvé");
        $actesPostWithoutSignature->postAction();
    }

    /**
     * @throws RedirectException
     * @throws Exception
     */
    public function testPostNoRight()
    {
        $this->logAs(103);
        $transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_EN_ATTENTE_D_ETRE_SIGNEE);
        $actesPostWithoutSignature = self::getContainer()->get(ActesPostWithoutSignatureController::class);
        self::getContainer()->get(Environnement::class)->post()->set('id', $transaction_id);
        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage("Vous n'avez pas le droit de faire cela");
        $actesPostWithoutSignature->postAction();
    }

    /**
     * @throws Exception
     */
    public function testPostNoRgs2Stars()
    {
        $transaction_id = $this->createTransaction(ActesStatusSQL::STATUS_EN_ATTENTE_D_ETRE_SIGNEE);
        $_SERVER['SSL_CLIENT_VERIFY'] = null;
        $server = [
            'SSL_CLIENT_VERIFY' => 'SUCCESS',
            'SSL_CLIENT_S_DN' => 'test_subject',
            'SSL_CLIENT_I_DN' => 'test_issuer',
            'TESTING_CERTIFICATE_HASH' => 'Q1pUbEb5DK53BkYf0arDl/3zl5U=',
        ];
        $auth = $this->getAuthentication(server: $server);
        $this->getContainer()->set(Authentification::class, $auth);
        $this->getObjectInstancier()->get(Environnement::class)->post()->set('id', $transaction_id);

        $actesPostWithoutSignature = $this->getObjectInstancier()->get(ActesPostWithoutSignatureController::class);
        try {
            $actesPostWithoutSignature->postAction();
            $this->fail();
        } catch (Exception $e) {
            $this->assertMatchesRegularExpression(
                "#La télétransmission nécessite un certificat RGS<br/>Erreur : Impossible de vérifier la connexion HTTPS#",
                $e->getMessage()
            );
        }
    }

    public function getMockedRgsConnexion(): \PHPUnit\Framework\MockObject\MockObject|RgsConnexion
    {
        $rgsConnexion = $this->getMockBuilder(RgsConnexion::class)->disableOriginalConstructor()->getMock();
        $rgsConnexion->method('isRgsConnexion')->willReturn(false);
        return $rgsConnexion;
    }

    protected function getActesTransactionsSQL(): ActesTransactionsSQL
    {
        return self::getContainer()->get(ActesTransactionsSQL::class);
    }

    public function getObjectInstancier()
    {
        return self::getContainer();
    }
}
