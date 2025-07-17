<?php

declare(strict_types=1);

namespace S2low\Tests\Controller;

use Exception;
use IntegrationTests\S2lowIntegrationTestCase;
use PHPUnit;
use PHPUnit\Framework\MockObject\MockObject;
use S2low\Controller\ActesSAEApiController;
use S2low\DTO\SAEStateTransitionRequest;
use S2low\Enum\UserRole;
use S2low\Kernel;
use S2low\Services\Actes\ActesSAEStateTransitionner;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\User;
use S2lowLegacy\Controller\Controller;
use S2lowTestCase;

class ActesSAEApiControllerTest extends S2lowIntegrationTestCase
{
    use PHPUnit\ActesUtilitiesTestTrait;

    private ActesTransactionsSQL $actesTransactionsSQL;
    private User|MockObject $mockUser;
    private ActesSAEApiController $actesSAEApiController;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actesTransactionsSQL = self::getContainer()->get(ActesTransactionsSQL::class);

        $this->actesSAEApiController = self::getContainer()->get(ActesSAEApiController::class);
        $this->actesSAEApiController->setContainer(self::getContainer());
    }

    public function testNoArchivistRights(): void
    {
        static::assertSame(
            '{"error":"L\u0027utilisateur n\u0027est pas archiviste"}',
            $this->actesSAEApiController->manageSAEState(
                new SAEStateTransitionRequest(1, 1)
            )->getContent()
        );
    }

    public function testNoTransaction(): void
    {
        $this->setUserWithRole(UserRole::Archiviste);
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
        $this->setUserWithRole(UserRole::Archiviste);
        $this->setUserAuthority(2);

        $created_trans_id = $this->createTransaction(
            status: ActesStatusSQL::STATUS_ACQUITTEMENT_RECU
        );

        static::assertSame(
            '{"error":"Mauvaise collectivite"}',
            $this->actesSAEApiController->manageSAEState(new SAEStateTransitionRequest($created_trans_id, ActesStatusSQL::STATUS_POSTE))
                ->getContent()
        );
    }

    /**
     * @dataProvider wrongStatuses
     */
    public function testTransactionWrongStatuses(int $inputStatus, int $outputStatus, string $message): void
    {
        $this->setUserWithRole(UserRole::Archiviste);
        $created_trans_id = $this->createTransaction($inputStatus);

        static::assertSame(
            sprintf('{"error":"%s"}', $message),
            $this->actesSAEApiController
                ->manageSAEState(new SAEStateTransitionRequest($created_trans_id, $outputStatus))
                ->getContent()
        );
    }

    public function wrongStatuses(): iterable
    {
        yield [
            ActesStatusSQL::STATUS_POSTE,ActesStatusSQL::STATUS_ENVOYE_AU_SAE,
            'Transition depuis le statut 1 impossible'];

        yield [ActesStatusSQL::STATUS_ACQUITTEMENT_RECU,ActesStatusSQL::STATUS_POSTE,
            'Transition vers le statut 1 impossible'];

        yield [ActesStatusSQL::STATUS_ENVOYE_AU_SAE,ActesStatusSQL::STATUS_ENVOYE_AU_SAE,
            'Transition entre deux status identiques (12) impossible'];
    }

    /**
     * @throws Exception
     */
    public function testSuccessfulStatusSwitch(): void
    {
        $this->setUserWithRole(UserRole::Archiviste);
        $created_trans_id = $this->createTransaction(ActesStatusSQL::STATUS_ACQUITTEMENT_RECU);

        static::assertSame(
            '{"status":"ok"}',
            $this->actesSAEApiController
                ->manageSAEState(new SAEStateTransitionRequest(
                    $created_trans_id,
                    ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE
                ))
                ->getContent()
        );

        static::assertSame(
            ActesStatusSQL::STATUS_EN_ATTENTE_TRANMISSION_SAE,
            $this->actesTransactionsSQL->getInfo($created_trans_id)['last_status_id']
        );
    }

    protected function getActesTransactionsSQL(): ActesTransactionsSQL
    {
        return self::getContainer()->get(ActesTransactionsSQL::class);
    }
}
