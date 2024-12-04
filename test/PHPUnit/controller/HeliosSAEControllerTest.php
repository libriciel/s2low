<?php

declare(strict_types=1);

namespace PHPUnit\controller;

use Exception;
use HeliosUtilitiesTestTrait;
use S2lowLegacy\Class\helios\HeliosStatusSQL;
use S2lowLegacy\Controller\HeliosSAEController;
use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Lib\RedirectException;
use S2lowLegacy\Model\HeliosTransactionsSQL;
use S2lowTestCase;

class HeliosSAEControllerTest extends S2lowTestCase
{
    use HeliosUtilitiesTestTrait;

    /**
     * @var \S2lowLegacy\Controller\HeliosSAEController
     */
    private HeliosSAEController $heliosSAEController;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->setRGSAuthentification();
    }

    protected function tearDown(): void
    {
        $_POST = [];
        parent::tearDown();
    }

    public function testUserCannotAccess(): void
    {
        $this->setUserAuthentification();
        $this->initController();
        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage('Accès refusé');
        $this->heliosSAEController->changeStatusAction();
    }

    public function testSuperAdminNoTransaction(): void
    {
        $this->setSuperAdminAuthentication();
        $this->initController();
        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage('Cette transaction n\'existe pas');

        $this->heliosSAEController->getRecuperateurPost()->set('transaction_id', 1);
        $this->heliosSAEController->getRecuperateurPost()->set('status_id', HeliosStatusSQL::POSTE);

        $this->heliosSAEController->changeStatusAction();
    }

    public function testSuperAdminOneTransactionChangeImpossible(): void
    {
        $this->setSuperAdminAuthentication();
        $this->initController();

        $transaction_id = $this->createTransaction(1, HeliosStatusSQL::POSTE);

        $this->heliosSAEController->getRecuperateurPost()->set('transaction_id', $transaction_id);
        $this->heliosSAEController->getRecuperateurPost()->set('status_id', HeliosStatusSQL::POSTE);

        try {
            $this->heliosSAEController->changeStatusAction();
        } catch (RedirectException $exception) {
            self::assertInstanceOf(RedirectException::class, $exception);
        }

        static::assertSame(
            'Impossible de changer le status de la transaction',
            $this->getObjectInstancier()->get(Environnement::class)->session()->get('error')
        );

        static::assertSame(
            HeliosStatusSQL::POSTE,
            $this->getHeliosTransactionsSQL()->getLastStatusInfo($transaction_id)['status_id']
        );
    }

    public function testSuperAdminOneTransactionChangePossible(): void
    {
        $this->setSuperAdminAuthentication();
        $this->initController();

        $transaction_id = $this->createTransaction(1, HeliosStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE);

        $this->heliosSAEController->getRecuperateurPost()->set('transaction_id', $transaction_id);
        $this->heliosSAEController->getRecuperateurPost()->set('status_id', HeliosStatusSQL::ACCEPTER_PAR_LE_SAE);

        try {
            $this->heliosSAEController->changeStatusAction();
        } catch (RedirectException $exception) {
            static::assertStringContainsString('Redirect to', $exception->getMessage());
        }

        static::assertSame(
            'Le status de la transaction a été modifiée',
            $this->getObjectInstancier()->get(Environnement::class)->session()->get('error')
        );

        static::assertSame(
            HeliosStatusSQL::ACCEPTER_PAR_LE_SAE,
            $this->getHeliosTransactionsSQL()->getLastStatusInfo($transaction_id)['status_id']
        );
    }

    public function testArchOneTransactionChangePossible(): void
    {
        $this->setArchAuthentification();
        $this->initController();

        $transaction_id = $this->createTransaction(1, HeliosStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE);

        $this->heliosSAEController->getRecuperateurPost()->set('transaction_id', $transaction_id);
        $this->heliosSAEController->getRecuperateurPost()->set('status_id', HeliosStatusSQL::ACCEPTER_PAR_LE_SAE);

        try {
            $this->heliosSAEController->changeStatusAction();
        } catch (RedirectException $exception) {
            static::assertStringContainsString('Redirect to', $exception->getMessage());
        }

        static::assertSame(
            'Le status de la transaction a été modifiée',
            $this->getObjectInstancier()->get(Environnement::class)->session()->get('error')
        );

        static::assertSame(
            HeliosStatusSQL::ACCEPTER_PAR_LE_SAE,
            $this->getHeliosTransactionsSQL()->getLastStatusInfo($transaction_id)['status_id']
        );
    }

    public function testArchOneTransactionDifferentAuthorities(): void
    {
        $this->setArchAuthentification();
        $this->initController();

        $transaction_id = $this->createTransaction(2, HeliosStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE);

        $this->heliosSAEController->getRecuperateurPost()->set('transaction_id', $transaction_id);
        $this->heliosSAEController->getRecuperateurPost()->set('status_id', HeliosStatusSQL::ACCEPTER_PAR_LE_SAE);

        try {
            $this->heliosSAEController->changeStatusAction();
        } catch (RedirectException $exception) {
            self::assertMatchesRegularExpression(
                '%Accès refusé%',
                $exception->getMessage(),
            );
        }

        static::assertSame(
            HeliosStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE,
            $this->getHeliosTransactionsSQL()->getLastStatusInfo($transaction_id)['status_id']
        );
    }

    public function getHeliosTransactionsSQL(): HeliosTransactionsSQL
    {
        return $this->getObjectInstancier()->get(HeliosTransactionsSQL::class);
    }

    /**
     * @return void
     */
    private function initController(): void
    {
        $this->heliosSAEController = new HeliosSAEController($this->getObjectInstancier());
        $this->heliosSAEController->verifUser();
    }
}
