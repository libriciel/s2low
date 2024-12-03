<?php

declare(strict_types=1);

namespace PHPUnit\controller;

use S2lowLegacy\Controller\HeliosSAEController;
use S2lowLegacy\Lib\RedirectException;
use S2lowTestCase;

class HeliosSAEControllerTest extends S2lowTestCase
{
    /**
     * @var \S2lowLegacy\Controller\HeliosSAEController
     */
    private HeliosSAEController $heliosSAEController;

    public function testUserCannotAccess()
    {
        $this->setRGSAuthentification();
        $this->setUserAuthentification();
        $this->heliosSAEController = new HeliosSAEController($this->getObjectInstancier());
        $this->heliosSAEController->verifUser();
        $this->expectException(RedirectException::class);
        $this->expectExceptionMessage('Accès refusé');
        $this->heliosSAEController->changeStatusAction();
    }
}
