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
            Helpers :: returnAndExit(1, "Erreur d'initialisation du module", WEBSITE_SSL);
        }

        $me = new User();

        $sortie = "";

        if (!$me->authenticate()) {
            Helpers :: returnAndExit(1, "Échec de l'authentification", Helpers::getLink("connexion-status"));
        }

// Un super admin ne peut pas accéder à cette page
        if (!$module->isActive() || $me->isGroupAdminOrSuper() || !$me->canEdit($module->get("name"))) {
            Helpers :: returnAndExit(1, "Accès refusé", WEBSITE_SSL);
        }

        $liste_id = array ();

        if (Helpers :: getVarFromPost("id")) {
            $liste_id[] = Helpers :: getVarFromPost("id");
        } else {
            $liste_id = Helpers :: getVarFromPost("liste_id");
        }

        if (! $liste_id) {
            Helpers :: returnAndExit(1, "Pas d'identifiant de transaction spécifié.", Helpers::getLink("/modules/actes/index.php"));
        }

        $msg = "";

        foreach ($liste_id as $id) {
            $severity = 1;
            $trans = new ActesTransaction();
            $trans->setId($id);

            if (! $trans->init()) {
                Helpers :: returnAndExit(1, "Erreur d'initialisation de la transaction.", Helpers::getLink("/modules/actes/index.php"));
            }

            $owner = new User($trans->get("user_id"));
            $owner->init();

            //Vérification du type de transaction
            if (!$trans->isType(TypeTransaction::TransmissionActe)) {
                Helpers::returnAndExit(
                    1,
                    'Ce type de transaction ne peut pas être notifié.',
                    Helpers::getLink('/modules/actes/actes_transac_show.php?id=') . $trans->getId()
                );
            }

            // Vérification des permissions
            if (!($me->isAnyAdmin() && $me->get("authority_id") == $owner->get("authority_id")) && !($me->canEdit($module->get("name")))) {
                Helpers :: returnAndExit(1, "Accès refusé.", Helpers::getLink("/modules/actes/index.php"));
            }

            $broadcastEmail = Helpers :: getVarFromPost("broadcast_email");
            if ($broadcastEmail) {
                if ($trans->setNotification(implode(',', Helpers :: getVarFromPost("broadcast_email")), (Helpers :: getVarFromPost("send_sources") == 'on') ? 1 : 0)) {
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
            $retour = Helpers::getLink("/modules/actes/actes_transac_show.php?id=") . $liste_id[0];
        } else {
            $retour = Helpers::getLink("/modules/actes/index.php");
        }
        $status = 0;
        Helpers :: returnAndExit($status, $sortie, $retour);
    }
}
