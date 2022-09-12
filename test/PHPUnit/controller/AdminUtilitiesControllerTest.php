<?php

use S2lowLegacy\Class\Mailer;
use S2lowLegacy\Class\MailerFactory;
use S2lowLegacy\Controller\AdminUtilitiesController;
use S2lowLegacy\Lib\Environnement;
use S2lowLegacy\Lib\RedirectException;

class AdminUtilitiesControllerTest extends S2lowTestCase
{
    public function testIndex()
    {
        $this->setSuperAdminAuthentication();
        $this->getObjectInstancier()->get(AdminUtilitiesController::class)->indexAction();
        $this->noAssertion();
    }
}
