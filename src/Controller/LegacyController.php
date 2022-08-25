<?php

namespace S2low\Controller;

use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LegacyController extends AbstractController
{
    public function loadLegacyScript(string $requestPath, string $legacyScript): StreamedResponse
    {
        $serverVariablesToSet['PHP_SELF'] = $requestPath;
        $serverVariablesToSet['SCRIPT_NAME'] = $requestPath;
        $serverVariablesToSet['SCRIPT_FILENAME'] = $legacyScript;

        return new StreamedResponse(
            function () use ($legacyScript, $serverVariablesToSet) {

                foreach ($serverVariablesToSet as $key => $value) {
                    $_SERVER[$key] = $value;
                }

                chdir(dirname($legacyScript));

                try {
                    require $legacyScript;
                } catch (Exception $exception) {
                    var_dump($exception->getMessage()); //TODO : utiliser la façon Symfony standard de traiter les exceptions
                }
            }
        );
    }
}
