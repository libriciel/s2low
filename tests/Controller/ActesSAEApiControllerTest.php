<?php

declare(strict_types=1);

namespace S2low\Tests\Controller;

use PHPUnit;
use PHPUnit\Framework\MockObject\MockObject;
use S2low\Controller\ActesSAEApiController;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\User;
use S2lowLegacy\Controller\Controller;
use S2lowTestCase;

class ActesSAEApiControllerTest extends S2lowTestCase
{
    use PHPUnit\ActesUtilitiesTestTrait;

    private ActesTransactionsSQL $actesTransactionsSQL;
    private User|MockObject $mockUser;
    private ActesSAEApiController $actesSAEApiController;

    protected function setUp(): void
    {
        parent::setUp();
        $legacyController = $this->getMockBuilder(Controller::class)
            ->disableOriginalConstructor()->getMock();
        $this->actesTransactionsSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);

        $this->mockUser = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()->getMock();
        $legacyController->method('getUser')->willReturn($this->mockUser);
        $this->actesSAEApiController = new ActesSAEApiController($legacyController, $this->actesTransactionsSQL);
    }

    public function testNoArchivistRights()
    {
        $this->mockUser->expects(static::once())->method('hasArchivistsRights')->willReturn(false);

        static::assertEquals(
            '{"error":"Pas les bons droits"}',
            $this->actesSAEApiController->manageSAEState(1, 1)->getContent()
        );
    }

    public function testNoTransaction()
    {
        $this->mockUser->expects(static::once())->method('hasArchivistsRights')->willReturn(true);

        static::assertEquals(
            '{"error":"Transaction 1165464894 non existante"}',
            $this->actesSAEApiController->manageSAEState(1165464894, 1)->getContent()
        );
    }

    /**
     * @throws \PHPUnit\Exception
     */
    public function testTransactionWrongAuthority()
    {
        $this->mockUser->method('hasArchivistsRights')->willReturn(true);
        $this->mockUser->method('get')->with('authority_id')->willReturn(2);
        // La transaction est créé avec l'autorité 1, l'user ne doit donc normalement pas y accéder
        $created_trans_id = $this->createTransaction(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU);

        static::assertEquals(
            '{"error":"Mauvaise collectivite"}',
            $this->actesSAEApiController->manageSAEState($created_trans_id, 1)->getContent()
        );
    }

    /**
     * @throws \PHPUnit\Exception
     */
    public function testTransactionWrongTransactionStatus()
    {
        $this->mockUser->method('hasArchivistsRights')->willReturn(true);
        $this->mockUser->method('get')->with('authority_id')->willReturn(1);
        // La transaction est créé avec l'autorité 1, l'user ne doit y accéder
        $created_trans_id = $this->createTransaction(ActesStatusSQL::STATUS_POSTE);

        static::assertEquals(
            '{"error":"Transition depuis le statut 1 impossible"}',
            $this->actesSAEApiController->manageSAEState($created_trans_id, 1)->getContent()
        );
    }

    /**
     * @throws \PHPUnit\Exception
     */
    public function testTransactionWrongOutputStatus()
    {
        $this->mockUser->method('hasArchivistsRights')->willReturn(true);
        $this->mockUser->method('get')->with('authority_id')->willReturn(1);
        // La transaction est créé avec l'autorité 1, l'user ne doit y accéder
        $created_trans_id = $this->createTransaction(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU);

        static::assertEquals(
            '{"error":"Transition vers le statut 1 impossible"}',
            $this->actesSAEApiController->manageSAEState($created_trans_id, 1)->getContent(),
        );
    }

    /**
     * @throws \PHPUnit\Exception
     */
    public function testSuccessfulStatusSwitch()
    {
        $this->mockUser->method('hasArchivistsRights')->willReturn(true);
        $this->mockUser->method('get')->with('authority_id')->willReturn(1);
        // La transaction est créé avec l'autorité 1, l'user ne doit y accéder
        $created_trans_id = $this->createTransaction(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU);

        static::assertEquals(
            '{"status":"ok"}',
            $this->actesSAEApiController
                ->manageSAEState($created_trans_id, ActesStatusSQL::STATUS_ERREUR_LORS_DE_L_ENVOI_SAE)
                ->getContent(),
        );

        static::assertEquals(
            ActesStatusSQL::STATUS_ERREUR_LORS_DE_L_ENVOI_SAE,
            $this->actesTransactionsSQL->getInfo($created_trans_id)['last_status_id']
        );
    }
}
