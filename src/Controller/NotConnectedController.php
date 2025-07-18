<?php

namespace S2low\Controller;

use S2lowLegacy\Class\HTMLLayout;
use S2lowLegacy\Class\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

class NotConnectedController extends AbstractController
{
    #[Route(
        path: '/connexion-status/',
        name: 'app_connection-status',
    )]
    public function handleRequest(): Response
    {
        return new StreamedResponse(
            function () {
                $doc = new HTMLLayout();

                $doc->setTitle(WEBSITE_TITLE);
                $doc->openContainer();
                $doc->openContent();
                $doc->addBody("<h1>Echec de connexion</h1><p>Si vous avez été redirigé vers cette page, le certificat présenté ne permet pas l'authentification sur la plateforme.</p>");
                $doc->closeContent();
                $doc->closeContainer();
                $doc->buildFooter();
                $doc->display();
            }
        );
    }
}
