<?php

namespace S2lowLegacy\Class;

class MailInit
{
    /**
     * @return array|void
     */
    public static function getIdentificationParameters()
    {
        $moduleSQL = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2lowLegacy\Model\ModuleSQL::class);
        $module = $moduleSQL->initByName("mail");
        if (!$module) {
            $_SESSION["error"] = "Erreur d'initialisation du module";
            header("Location: " . WEBSITE_SSL);
            exit();
        }

        $me = new User();

        if (!$me->authenticate()) {
            $_SESSION["error"] = "Échec de l'authentification";
            header("Location: " . Helpers::getLink("connexion-status"));
            exit();
        }

        if ($me->isGroupAdminOrSuper() || !$module->isActive() || !$me->canEdit($module->get("name"))) {
            $_SESSION["error"] = "Accès refusé";
            header("Location: " . WEBSITE_SSL);
            exit();
        }

        $myAuthority = new Authority($me->get("authority_id"));
        return array($module, $me, $myAuthority);
    }
}
