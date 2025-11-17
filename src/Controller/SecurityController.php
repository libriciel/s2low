<?php

namespace S2low\Controller;

use S2lowLegacy\Class\HttpsConnexion;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Contrôleur de sécurité pour S2low
 * Gère les pages de login et logout
 */
class SecurityController extends AbstractController
{
    public function __construct(
        private HttpsConnexion $httpsConnexion
    ) {}

    /**
     * Page de connexion
     */
    #[Route('/security/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Si l'utilisateur est déjà connecté, rediriger vers la page d'accueil
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        // Récupérer l'erreur de login si présente
        $error = $authenticationUtils->getLastAuthenticationError();

        // Dernier username saisi
        $lastUsername = $authenticationUtils->getLastUsername();

        // Récupérer infos certificat pour affichage
        $certInfo = $this->getCertificateInfo();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'certificate' => $certInfo
        ]);
    }

    /**
     * Route de logout (gérée automatiquement par Symfony)
     */
    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): void
    {
        // Cette méthode peut rester vide - elle sera interceptée par Symfony Security
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    /**
     * Page d'accueil après connexion
     */
    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        // Vérifier que l'utilisateur est connecté
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();

        return $this->render('security/home.html.twig', [
            'user' => $user
        ]);
    }

    /**
     * Récupère les informations du certificat SSL client
     */
    private function getCertificateInfo(): ?array
    {
        try {
            $certInfo = $this->httpsConnexion->getCertificateInfo();

            if (!$certInfo) {
                return null;
            }

            return [
                'subject_dn' => $certInfo['subject_dn'] ?? null,
                'issuer_dn' => $certInfo['issuer_dn'] ?? null,
                'certificate_hash' => $certInfo['certificate_hash'] ?? null,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }
}
