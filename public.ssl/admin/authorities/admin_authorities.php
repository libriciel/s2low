<?php

namespace App\Http\Controllers\Admin;

use S2low\Legacy\S2lowLegacyCommandInSymfonyContainer;
use S2lowLegacy\Lib\FrontController;

// phpcs:ignore Squiz.Classes.ValidClassName.NotPascalCase
class Admin_authorities extends S2lowLegacyCommandInSymfonyContainer
{
    public function __construct(
        private FrontController $frontController
    ) {
    }
    public function launchLegacyCommand(): void
    {
        $this->frontController->go('Admin', 'authorities');
    }
}
