<?php

class UserPermission {

    public function checkSuperAdmin(){

        $me = new User();

        if (! $me->authenticate()) {
            $_SESSION["error"] = "Échec de l'authentification";
            header("Location: " . WEBSITE);
            exit();
        }

        if (! $me->isSuper()) {
            $_SESSION["error"] = "Accès refusé";
            header("Location: " . WEBSITE_SSL);
            exit();
        }

    }


}