<?php

namespace S2low\Security;

use Symfony\Component\HttpFoundation\Response;

trait CreateSecurityResponseTrait
{
    public function createConnexionImpossibleResponse(): Response
    {
        $retour = "KO\nLa connexion n&#039;a pas pu ?tre ?tablie\n";
        $retourIso = mb_convert_encoding($retour, 'ISO-8859-1', 'UTF-8');
        return new Response($retourIso);
    }
}
