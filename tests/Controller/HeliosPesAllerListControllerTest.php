<?php

declare(strict_types=1);

namespace S2low\Tests\Controller;

use PHPUnit\Framework\MockObject\MockObject;
use S2low\Controller\HeliosPesAllerListController;
use S2low\Kernel;
use S2lowLegacy\Class\User;
use S2lowLegacy\Controller\Controller;
use S2lowLegacy\Lib\Recuperateur;
use S2lowLegacy\Model\HeliosTransactionsSQL;
use S2lowTestCase;

class HeliosPesAllerListControllerTest extends S2lowTestCase
{
    private User|MockObject $mockUser;
    private HeliosPesAllerListController $heliosPesAllerListController;

    protected function setUp(): void
    {
        parent::setUp();
        $legacyController = $this->getMockBuilder(Controller::class)
            ->disableOriginalConstructor()->getMock();
        $heliosTransactionsSQL = $this->getObjectInstancier()->get(HeliosTransactionsSQL::class);

        $this->mockUser = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()->getMock();

        $mockRecuperateurGet = $this->getMockBuilder(Recuperateur::class)
            ->disableOriginalConstructor()->getMock();

        $mockRecuperateurGet->method('getInt')->willReturn(0);

        $legacyController->method('getUser')->willReturn($this->mockUser);
        $legacyController->method('getRecuperateurGet')->willReturn($mockRecuperateurGet);

        $this->heliosPesAllerListController = new HeliosPesAllerListController(
            $legacyController,
            $heliosTransactionsSQL
        );

        $kernel = new Kernel('test', true);
        $kernel->boot();

        $this->heliosPesAllerListController->setContainer($kernel->getContainer());
    }

    public function testNoArchivistRights(): void
    {
        $this->mockUser->expects(static::once())->method('isArchivist')->willReturn(false);

        static::assertSame(
            '{"error":"L\u0027utilisateur n\u0027est pas archiviste"}',
            $this->heliosPesAllerListController->list()->getContent()
        );
    }
    public function testNoTransaction(): void
    {
        $this->mockUser->expects(static::once())->method('isArchivist')->willReturn(true);

        static::assertSame(
            '{"status_id":"0","authority_id":"0","offset":"0","limit":"0","transactions":[]}',
            $this->heliosPesAllerListController->list()->getContent()
        );
    }
}
