<?php

namespace S2low\Controller;

use S2low\Security\SecurityUser;
use S2lowLegacy\Model\NounceSQL;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AuthenticationNounceController extends AbstractController
{
    public function __construct(
        private readonly NounceSQL $nounceSQL,
    ) {
    }

    #[Route('/api/get-nounce.php', name: 'get-nounce', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function createNounce(Request $request): Response
    {
        /** @var SecurityUser $user */
        $user = $this->getUser();

        $login = $request->getUser();
        $password = $request->getPassword();

        if (empty($login) || empty($password)) {
            return new Response(
                "La fonction n'est utilisable qu'avec un login+mot de passe HTTP",
                401,
                ['WWW-Authenticate' => 'Basic realm="API S2low"']
            );
        }


        $authorityId = $user->getAuthorityId();

        $nounce = $this->nounceSQL->create(
            $login,
            $password,
            $authorityId
        );

        return new JsonResponse(
            ['nounce' => $nounce]
        );
    }
}
