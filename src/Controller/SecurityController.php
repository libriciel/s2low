<?php

namespace S2low\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class SecurityController extends AbstractController
{
    #[Route('/deconnexion', name: 'app_logout', methods: ['GET'])]
    public function logout(): void
    {
        // Ce contrôleur ne sera jamais exécuté car Symfony intercepte la route
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
