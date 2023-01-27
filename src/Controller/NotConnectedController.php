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
    /**
     * @Route("/connexion-status/", name="app_connection-status")
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function handleRequest(): Response
    {
        $me = new User();

        $certificateInfo = $me->getCertificateInfo();
        $subject = $certificateInfo['subject'];

        return new StreamedResponse(
            function () use ($subject) {
                $doc = new HTMLLayout();

                $doc->setTitle(WEBSITE_TITLE);
                $doc->openContainer();
                $doc->openContent();
                $doc->afficheErrors();
                $doc->addBody("<h1>Diagnostic de connexion</h1><p>$subject</p>");
                $doc->closeContent();
                $doc->closeContainer();
                $doc->buildFooter();
                $doc->display();
            }
        );
    }
}
