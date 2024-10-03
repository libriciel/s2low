<?php

namespace S2low\Controller;

use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class LegacyController extends AbstractController
{
    public function loadLegacyScript(string $requestPath, string $legacyScript): Response
    {
        $_SERVER['PHP_SELF'] = $requestPath;
        $_SERVER['SCRIPT_NAME'] = $requestPath;
        $_SERVER['SCRIPT_FILENAME'] = $legacyScript;

        chdir(dirname($legacyScript));

        ob_start();
        try {
            require $legacyScript;
        } catch (Exception $e) {
            var_dump($e->getMessage());
        }
        $content = (string)ob_get_clean();

        $headers = [];
        foreach (headers_list() as $header) {
            $trimmed = explode(': ', $header);
            $headers[$trimmed[0]] = $trimmed[1];
        }
        header_remove_wrapper();
        return new Response($content, 200, $headers);
    }
}
