<?php

namespace S2low\Controller;

use S2lowLegacy\Class\HTMLLayout;
use S2lowLegacy\Class\User;
use S2lowLegacy\Model\UserSQL;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

class NotConnectedController extends AbstractController
{
    #[Route(
        path: '/connexion-status/',
        name: 'connection-status',
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

    #[Route(
        path: '/api/test-connexion.php',
        name: 'test_connection',
    )]
    public function testConnexion(): Response
    {
        return new Response('OK');
    }

    #[Route(
        path: '/admin/users/api-list-login.php',
        name: 'list_login',
    )]
    public function listLogin(
        UserSQL $userSQL,
    ): Response {

        $me = new User();
        $certificateInfo = $me->getCertificateInfo();

        $all_user = $userSQL->getInfoFromCertificateInfo($certificateInfo);
        $res = "";
        foreach ($all_user as $user) {
            $res .= mb_convert_encoding($user['login'], 'ISO-8859-1') . "\n";
        }

        return new Response($res);
    }
}
