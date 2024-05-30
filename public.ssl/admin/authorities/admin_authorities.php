<?php

namespace App\Http\Controllers\Admin;

use S2low\Tests\Legacy\S2lowLegacyCommand;
use S2lowLegacy\Lib\FrontController;
use Symfony\Component\HttpFoundation\Response;

// phpcs:ignore
class Admin_authorities extends S2lowLegacyCommand
{
    public function doTheWork(FrontController $frontController)
    {
        ob_start();
        $this->work($frontController);
        $output = ob_get_clean();
        return new Response($output, 200);
    }
    public function work(FrontController $frontController): void
    {
        $frontController->go('Admin', 'authorities');
    }
}
