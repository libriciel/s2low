<?php

use IntegrationTests\S2lowIntegrationTestCase;
use S2low\Enum\UserRole;
use S2lowLegacy\Controller\AdminUtilitiesController;

class AdminUtilitiesControllerTest extends S2lowIntegrationTestCase
{
    public function testIndex()
    {
        $this->setUserWithRole(UserRole::SuperAdministrateur);
        self::getContainer()->get(AdminUtilitiesController::class)->indexAction();
        self::expectNotToPerformAssertions();
    }
}
