<?php

namespace S2low\Controller;

use ActesTransaction;
use Exception;
use S2lowLegacy\Class\actes\ActesNotification;
use S2lowLegacy\Class\actes\TypeTransaction;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;
use Symfony\Component\Routing\Annotation\Route;

class ActesNotificationController extends \Symfony\Bundle\FrameworkBundle\Controller\AbstractController
{
    private ActesNotification $actesNotification;

    public function __construct(ActesNotification $actesNotification)
    {
        $this->actesNotification = $actesNotification;
    }

    #[Route(
        path: '/modules/actes/actes_transac_notify.php',
        name: 'app_modules_actes_transac_notify',
    )]
    public function notify()
    {
        // Instanciation du module courant
        $module = new Module();
        if (!$module->initByName("actes")) {
            \S2lowLegacy\Class\Helpers\ResponseHelper::returnAndExit(1, "Erreur d'initialisation du module", WEBSITE_SSL);
        }

        $me = new User();

        $sortie = "";

        if (!$me->authenticate()) {
            \S2lowLegacy\Class\Helpers\ResponseHelper::returnAndExit(1, "Échec de l'authentification", \S2lowLegacy\Class\Helpers\UrlHelper::getLink("connexion-status"));
        }

// Un super admin ne peut pas accéder à cette page
        if (!$module->isActive() || $me->isGroupAdminOrSuper() || !$me->canEdit($module->get("name"))) {
            \S2lowLegacy\Class\Helpers\ResponseHelper::returnAndExit(1, "Accès refusé", WEBSITE_SSL);
        }

        $liste_id = array ();

        if (\S2lowLegacy\Class\Helpers\RequestHelper::getVarFromPost("id")) {
            $liste_id[] = \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromPost("id");
        } else {
            $liste_id = \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromPost("liste_id");
        }

        if (! $liste_id) {
            \S2lowLegacy\Class\Helpers\ResponseHelper::returnAndExit(1, "Pas d'identifiant de transaction spécifié.", \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/actes/index.php"));
        }

        $msg = "";

        foreach ($liste_id as $id) {
            $severity = 1;
            $trans = new ActesTransaction();
            $trans->setId($id);

            if (! $trans->init()) {
                \S2lowLegacy\Class\Helpers\ResponseHelper::returnAndExit(1, "Erreur d'initialisation de la transaction.", \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/actes/index.php"));
            }

            $owner = new User($trans->get("user_id"));
            $owner->init();

            //Vérification du type de transaction
            if (!$trans->isType(TypeTransaction::TransmissionActe)) {
                \S2lowLegacy\Class\Helpers\ResponseHelper::returnAndExit(
                    1,
                    'Ce type de transaction ne peut pas être notifié.',
                    \S2lowLegacy\Class\Helpers\UrlHelper::getLink('/modules/actes/actes_transac_show.php?id=') . $trans->getId()
                );
            }

            // Vérification des permissions
            if (!($me->isAdmin() && $me->get("authority_id") == $owner->get("authority_id")) && !($me->canEdit($module->get("name")))) {
                \S2lowLegacy\Class\Helpers\ResponseHelper::returnAndExit(1, "Accès refusé.", \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/actes/index.php"));
            }

            $broadcastEmail = \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromPost("broadcast_email");
            if ($broadcastEmail) {
                if ($trans->setNotification(implode(',', \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromPost("broadcast_email")), (\S2lowLegacy\Class\Helpers\RequestHelper::getVarFromPost("send_sources") == 'on') ? 1 : 0)) {
                    $severity = 1;
                    $msg = "Notification manuelle de la transaction " . $id;
                    $sortie .= $msg;
                } else {
                    $severity = 3;
                    $msg = "Erreur lors de la notification de la transaction " . $id;
                    $sortie .= $msg;
                }
            }
            try {
                $this->actesNotification->sendNotificationManuel($id);
            } catch (Exception $e) {
                $severity = 3;
                $msg = "Erreur lors de la notification : " . $e->getMessage();
                $sortie .= $msg;
            }
        }


        if (count($liste_id) == 1) {
            $retour = \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/actes/actes_transac_show.php?id=") . $liste_id[0];
        } else {
            $retour = \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/actes/index.php");
        }
        $status = 0;
        \S2lowLegacy\Class\Helpers\ResponseHelper::returnAndExit($status, $sortie, $retour);
    }
}
