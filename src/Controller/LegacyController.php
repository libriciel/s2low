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

        if($this->getParameter('kernel.environment')==='test'){ // Le client Symfony ne fonctionne pas sinon ...
            foreach (                                                 // TODO : vérifier
                [                                                     // Ces variables sont set au sein des tests unitaires
                    'SSL_CLIENT_VERIFY',
                    'SSL_CLIENT_S_DN',
                    'SSL_CLIENT_I_DN',
                    'SSL_CLIENT_CERT',
                    'HTTP_ORG_S2LOW_FORWARD_X509_IDENTIFICATION',
                    'TESTING_CERTIFICATE_HASH'
                ]
                as $key){
                $serverVariablesToSet[$key] = $request->server->get($key);
            }
        }

        return new StreamedResponse(
            function () use ($legacyScript, $serverVariablesToSet) {
                //$_SERVER['PHP_SELF'] = $requestPath;
                //$_SERVER['SCRIPT_NAME'] = $requestPath;
                //$_SERVER['SCRIPT_FILENAME'] = $legacyScript;

                foreach ($serverVariablesToSet as $key=>$value){
                    $_SERVER[$key] = $value;
                }

                chdir(dirname($legacyScript));

                require $legacyScript;
            }
        );
    }
}
