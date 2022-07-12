<?php

namespace S2low\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\HttpKernel;

class LegacyController extends AbstractController
{
    public function loadLegacyScript(string $requestPath, string $legacyScript, Request $request): StreamedResponse
    {
        $serverVariablesToSet['PHP_SELF'] = $requestPath;
        $serverVariablesToSet['SCRIPT_NAME'] = $requestPath;
        $serverVariablesToSet['SCRIPT_FILENAME'] = $legacyScript;

        return new StreamedResponse(
            function () use ($legacyScript, $serverVariablesToSet) {
                //$_SERVER['PHP_SELF'] = $requestPath;
                //$_SERVER['SCRIPT_NAME'] = $requestPath;
                //$_SERVER['SCRIPT_FILENAME'] = $legacyScript;

                foreach ($serverVariablesToSet as $key => $value) {
                    $_SERVER[$key] = $value;
                }

                chdir(dirname($legacyScript));

                try {
                    require $legacyScript;
                } catch (\Exception $exception) {
                    var_dump($exception->getMessage());
                }
            }
        );
    }
}
