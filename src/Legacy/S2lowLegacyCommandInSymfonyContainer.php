<?php

namespace S2low\Legacy;

use Symfony\Component\HttpFoundation\Response;

abstract class S2lowLegacyCommandInSymfonyContainer
{
    public function generateResponse(): Response
    {
        ob_start();
        $this->launchLegacyCommand();
        $output = ob_get_clean();
        return new Response($output, 200);
    }
    abstract public function launchLegacyCommand(): void;
}
