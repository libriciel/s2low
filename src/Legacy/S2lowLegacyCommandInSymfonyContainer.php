<?php

namespace S2low\Legacy;

use Symfony\Component\HttpFoundation\Response;

abstract class S2lowLegacyCommandInSymfonyContainer
{
    public function generateResponse(string $requestPath): Response
    {
        $_SERVER['PHP_SELF'] = $requestPath;
        $_SERVER['SCRIPT_NAME'] = $requestPath;
        ob_start();
        $this->launchLegacyCommand();
        $output = ob_get_clean();
        return new Response($output, 200);
    }
    abstract public function launchLegacyCommand(): void;
}
