<?php

namespace S2low\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LegacyController extends AbstractController
{
    public function loadLegacyScript(string $requestPath, string $legacyScript): StreamedResponse
    {

        return new StreamedResponse(
            function () use ($requestPath, $legacyScript) {
                $_SERVER['PHP_SELF'] = $requestPath;
                $_SERVER['SCRIPT_NAME'] = $requestPath;
                $_SERVER['SCRIPT_FILENAME'] = $legacyScript;

                chdir(dirname($legacyScript));

                require $legacyScript;
            }
        );
    }
}
