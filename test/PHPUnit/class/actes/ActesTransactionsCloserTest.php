<?php

declare(strict_types=1);

namespace PHPUnit\class\actes;

use Exception;
use PHPUnit\ActesUtilitiesTestTrait;
use S2lowLegacy\Class\actes\ActesEnvelopeSQL;
use S2lowLegacy\Class\actes\ActesRetriever;
use S2lowLegacy\Class\actes\ActesScriptHelper;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\ActesTransactionsCloser;
use S2lowLegacy\Class\actes\ActesTransactionsSQL;
use S2lowLegacy\Class\ActesWorkspaceForTests;
use S2lowLegacy\Class\S2lowLogger;
use S2lowLegacy\Lib\OpenStackSwiftWrapper;
use S2lowTestCase;

class ActesTransactionsCloserTest extends S2lowTestCase
{
    use ActesUtilitiesTestTrait;

    private ActesWorkspaceForTests $workspace;

    public function setUp(): void
    {
        parent::setUp();
        $this->workspace = new ActesWorkspaceForTests();
        $this->actesRetriever = new ActesRetriever(
            $this->getObjectInstancier()->get(OpenStackSwiftWrapper::class),
            $this->getObjectInstancier()->get(S2lowLogger::class),
            $this->workspace
        );
    }

    public function tearDown(): void
    {
        $this->workspace->clear();
    }
    /**
     * @throws Exception
     */
    public function testCloseAll()
    {

        $vieilleTransactionId = $this->createTransaction(
            ActesStatusSQL::STATUS_TRANSMIS,
            '',
            '1970-01-01'
        );
        $recenteTransactionId = $this->createTransaction(
            ActesStatusSQL::STATUS_TRANSMIS,
            '',
            date('Y-m-d H:i:s')
        );

        /** @var ActesTransactionsCloser $actesTransactionsCloser */
        $actesTransactionsCloser = new ActesTransactionsCloser(
            $this->getObjectInstancier()->get(ActesTransactionsSQL::class),
            $this->getObjectInstancier()->get(S2lowLogger::class),
            new ActesScriptHelper(
                $this->getObjectInstancier()->get(ActesTransactionsSQL::class),
                $this->getObjectInstancier()->get(ActesEnvelopeSQL::class),
                $this->getObjectInstancier()->get('actes_appli_trigramme'),
                $this->actesRetriever
            )
        );
        $actesTransactionsCloser->closeAll();

        /** @var ActesTransactionsSQL $actesTransactionsSQL */
        $actesTransactionsSQL = $this->getObjectInstancier()->get(ActesTransactionsSQL::class);
        $status_info = $actesTransactionsSQL->getLastStatusInfo($vieilleTransactionId);
        static::assertSame(ActesStatusSQL::STATUS_EN_ERREUR, $status_info['status_id']);
        static::assertSame('Fermeture automatique de la transaction de plus de 30 jours', $status_info['message']);

        $status_info = $actesTransactionsSQL->getLastStatusInfo($recenteTransactionId);
        static::assertSame(ActesStatusSQL::STATUS_TRANSMIS, $status_info['status_id']);
    }

    protected function getActesTransactionsSQL(): ActesTransactionsSQL
    {
        return $this->getObjectInstancier()->get(ActesTransactionsSQL::class);
    }

    public function getActesWorkspace(): ActesWorkspaceForTests
    {
        return $this->workspace;
    }
}
