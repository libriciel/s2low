<?php

namespace S2lowLegacy\Class;

class UserPermission
{
    public function checkSuperAdmin()
    {

        $me = new User();

        if (! $me->authenticate()) {
            $_SESSION["error"] = "Échec de l'authentification";
            header("Location: " . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("connexion-status"));
            exit();
        }

        if (! $me->isSuper()) {
            $_SESSION["error"] = "Accès refusé";
            header("Location: " . WEBSITE_SSL);
            exit();
        }
    }
}
