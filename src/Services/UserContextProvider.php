<?php

namespace S2low\Services;

use S2lowLegacy\Class\Authority;
use S2lowLegacy\Class\Connexion;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\UserContext;
use S2lowLegacy\Class\User;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\GroupSQL;
use S2lowLegacy\Model\UserSQL;

class UserContextProvider
{
    public function __construct(
        private readonly UserSQL $userSQL,
        private readonly AuthoritySQL $authoritySQL,
        private readonly GroupSQL $groupSQL,
    ) {
    }

    public function require()
    {
        // TODO : l'authentification sera gérée par Symfony Security
        // On pourra récupérer directement l'userId
        $connexion = new Connexion();
        $me = null;
        if (!$connexion->isConnected()) {
            $me = new User();

            if (!$me->authenticate()) {
                $_SESSION['error'] = "Échec de l'authentification";
                header('Location: ' . Helpers::getLink('connexion-status'));
                exit();
            }
        }

        $userInfo = $this->userSQL->getInfo($connexion->getId());

        $authorityInfo = $this->authoritySQL->getInfo($userInfo['authority_id']);


        $groupeInfo = null;
        if ($authorityInfo['authority_group_id']) {
            $groupeInfo = $this->groupSQL->getInfo($authorityInfo['authority_group_id']);
        }

        return new UserContext(
            $connexion,
            $me,
            $userInfo,
            $authorityInfo,
            $groupeInfo,
        );
    }
}
