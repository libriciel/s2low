<?php

namespace S2low\Controller;

use DateTimeImmutable;
use S2low\Security\SecurityUser;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\GroupSQL;
use S2lowLegacy\Model\MessageAdminSQL;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class HomeController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(
        #[CurrentUser]
        SecurityUser $user,
        AuthoritySQL $authoritySQL,
        GroupSQL $groupSQL,
        MessageAdminSQL $messageAdminSQL,
    ): Response {
        $authority = $authoritySQL->getInfo($user->getAuthorityId());
        $authorityName = $authority['name'];

        // Le groupe annoncé est celui de l'utilisateur : sa collectivité en désigne un par module,
        // et ce n'est pas d'elle qu'il tient son rôle d'administrateur de groupe.
        $authorityGroupId = $user->getAuthorityGroupId();
        $group = $authorityGroupId ? $groupSQL->getInfo($authorityGroupId) : false;
        $groupName = $group ? $group['name'] : 'Aucun groupe';

        $now = new DateTimeImmutable();
        $interval = $now->diff($user->getCertExpirationDate());
        $days_left = (int) $interval->format('%r%a');

        $messageAdminOrNull = $messageAdminSQL->getMessages();
        if (!empty($messageAdminOrNull)) {
            $messageAdmin = [
                'id' => $messageAdminOrNull['id'],
                'css_level' => $messageAdminOrNull['niveau'],
                'title' => $messageAdminOrNull['titre'],
                'message' => $messageAdminOrNull['message'],
            ];
        } else {
            $messageAdmin = null;
        }

        return $this->render('index.html.twig', [
            'nb_day_before_certificate_expire' => $days_left,
            'authority_name' => $authorityName,
            'group_name' => $groupName,
            'message_admin' => $messageAdmin,
        ]);
    }
}
