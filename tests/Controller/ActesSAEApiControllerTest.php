<?php

declare(strict_types=1);

namespace S2low\Tests\Controller;

use Exception;
use PHPUnit;
use PHPUnit\Framework\MockObject\MockObject;
use S2low\Controller\ActesSAEApiController;
use S2low\DTO\SAEStateTransitionRequest;
use S2low\Kernel;
use S2low\Services\Actes\ActesSAEStateTransitionner;
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
        $this->actesSAEApiController = new ActesSAEApiController(
            $legacyController,
            new ActesSAEStateTransitionner($this->actesTransactionsSQL)
        );

        $kernel = new Kernel('test', true);
        $kernel->boot();

        $this->actesSAEApiController->setContainer($kernel->getContainer());
    }

    public function testNoArchivistRights(): void
    {
        $this->mockUser->expects(static::once())->method('hasArchivistsRights')->willReturn(false);

        static::assertSame(
            '{"error":"Pas les bons droits"}',
            $this->actesSAEApiController->manageSAEState(
                new SAEStateTransitionRequest(1, 1)
            )->getContent()
        );
    }

    public function testNoTransaction(): void
    {
        $this->mockUser->expects(static::once())->method('hasArchivistsRights')->willReturn(true);

        static::assertSame(
            '{"error":"Transaction 1165464894 non existante"}',
            $this->actesSAEApiController->manageSAEState(
                new SAEStateTransitionRequest(1165464894, 1)
            )->getContent()
        );
    }

    /**
     * @throws Exception
     */
    public function testTransactionWrongAuthority(): void
    {
        $this->mockUser->method('hasArchivistsRights')->willReturn(true);
        $this->mockUser->method('get')->with('authority_id')->willReturn(2);
        // La transaction est créé avec l'autorité 1, l'user ne doit donc normalement pas y accéder
        $created_trans_id = $this->createTransaction(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU);

        static::assertSame(
            '{"error":"Mauvaise collectivite"}',
            $this->actesSAEApiController->manageSAEState(new SAEStateTransitionRequest($created_trans_id, 1))
                ->getContent()
        );
    }

    /**
     * @throws Exception
     */
    public function testTransactionWrongTransactionStatus(): void
    {
        $this->mockUser->method('hasArchivistsRights')->willReturn(true);
        $this->mockUser->method('get')->with('authority_id')->willReturn(1);
        // La transaction est créé avec l'autorité 1, l'user ne doit y accéder
        $created_trans_id = $this->createTransaction(ActesStatusSQL::STATUS_POSTE);

        static::assertSame(
            '{"error":"Transition depuis le statut 1 impossible"}',
            $this->actesSAEApiController->manageSAEState(new SAEStateTransitionRequest($created_trans_id, 1))
                ->getContent()
        );
    }

    /**
     * @throws Exception
     */
    public function testTransactionWrongOutputStatus(): void
    {
        $this->mockUser->method('hasArchivistsRights')->willReturn(true);
        $this->mockUser->method('get')->with('authority_id')->willReturn(1);
        // La transaction est créé avec l'autorité 1, l'user ne doit y accéder
        $created_trans_id = $this->createTransaction(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU);

        static::assertSame(
            '{"error":"Transition vers le statut 1 impossible"}',
            $this->actesSAEApiController->manageSAEState(new SAEStateTransitionRequest($created_trans_id, 1))
            ->getContent()
        );
    }

    /**
     * @throws Exception
     */
    public function testSuccessfulStatusSwitch(): void
    {
        $this->mockUser->method('hasArchivistsRights')->willReturn(true);
        $this->mockUser->method('get')->with('authority_id')->willReturn(1);
        // La transaction est créé avec l'autorité 1, l'user ne doit y accéder
        $created_trans_id = $this->createTransaction(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU);

        static::assertSame(
            '{"status":"ok"}',
            $this->actesSAEApiController
                ->manageSAEState(new SAEStateTransitionRequest(
                    $created_trans_id,
                    ActesStatusSQL::STATUS_ERREUR_LORS_DE_L_ENVOI_SAE
                ))
                ->getContent()
        );

        static::assertSame(
            ActesStatusSQL::STATUS_ERREUR_LORS_DE_L_ENVOI_SAE,
            $this->actesTransactionsSQL->getInfo($created_trans_id)['last_status_id']
        );
    }
}
