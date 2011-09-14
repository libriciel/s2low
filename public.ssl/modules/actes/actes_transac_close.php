<?php
require_once ("../../../config/config.php");
require_once (SITEROOT . '/class/include.class.php');
require_once (SITEROOT . '/public.ssl/modules/actes/class/ActesEnvelope.class.php');

// Instanciation du module courant
$module = new Module();
if (!$module->initByName("actes")) {
  Helpers::returnAndExit(1, "Erreur d'initialisation du module", WEBSITE_SSL);
}

$me = new User();

$sortie = "";

if (!$me->authenticate()) {
  Helpers::returnAndExit(1, "Échec de l'authentification", WEBSITE);
}

// Un super admin ne peut pas accéder à cette page
if (!$module->isActive() || $me->isGroupAdminOrSuper() || !$me->canEdit($module->get("name"))) {
  Helpers::returnAndExit(1, "Accès refusé", WEBSITE_SSL);
}

$liste_id = array ();

if (Helpers::getVarFromPost("id") != null) {
  $liste_id[0] = Helpers::getVarFromPost("id");
} else {
  $liste_id = Helpers::getVarFromPost("liste_id");
}

if (is_array($liste_id)) {
  foreach ($liste_id as $id) {

    $status = Helpers::getVarFromPost("status");

    $myAuthority = new Authority($me->get("authority_id"));

    $trans = new ActesTransaction();

    if (isset ($id) && !empty ($id)) {
      $trans->setId($id);
      if ($trans->init()) {
        $owner = new User($trans->get("user_id"));
        $owner->init();
      } else {
        Helpers::returnAndExit(1, "Erreur d'initialisation de la transaction.", WEBSITE_SSL . "/modules/actes/index.php");
      }
    } else {
      Helpers::returnAndExit(1, "Pas d'identifiant de transaction spécifié.", WEBSITE_SSL . "/modules/actes/index.php");
    }

    // Vérification du type de transaction
    if ($trans->get("type") != 1) {
      Helpers::returnAndExit(1, "Ce type de transaction ne peut pas être cloturé.", WEBSITE_SSL . "/modules/actes/actes_transac_show.php?id=" . $rel_trans->getId());
    }

    $envelope = new ActesEnvelope($trans->get("envelope_id"));
    $envelope->init();

    // Vérification des permissions
    if (!($me->isAdmin() && $me->get("authority_id") == $owner->get("authority_id")) && !($me->getId() == $envelope->get("user_id") && $me->canEdit($module->get("name")))) {
      Helpers::returnAndExit(1, "Accès refusé.", WEBSITE_SSL . "/modules/actes/index.php");
    }

    if ($status == "valid") {
      $new_status_id = 5;
    } elseif ($status == "invalid") {
      $new_status_id = 6;
    } else {
      Helpers::returnAndExit(1, "État incorrect.", WEBSITE_SSL . "/modules/actes/actes_transac_show.php?id=" . $id);
    }

    $types = ActesTransaction::getStatusList();

    if (! $trans->setNewStatus($new_status_id, "Fermeture par l'utilisateur " . $me->getPrettyName())) {
      $msg = "Erreur lors de la tentative de passage de la transaction n°" . $trans->getId() . " vers l'état " . $types[$new_status_id] . ".\n";
      $sortie .= $msg;
      $severity = 3;
      $status = 1;
    } else {
      $msg = "Passage de la transaction n°" . $trans->getId() . " à l'état « " . $types[$new_status_id] . " ». Résultat ok.";
      $sortie .= $msg;
      $severity = 1;
      $status = 0;

//	  // Suppression de l'archive si tous les actes contenus sont clos
//	  $env = new ActesEnvelope($trans->get("envelope_id"));
//	  $env->init();
//	  if ($env->deleteArchiveFileIfAllClose()) {
//		$str = " Fin du stockage provisoire.\n";
//		$msg .= $str;
//		$sortie .= $str;
//	  }
    }

    if (! Log::newEntry(LOG_ISSUER_NAME, $msg, $severity, false, 'USER', $module->get("name"), $me)) {
      $msg .= "\nErreur de journalisation.\n";
      $sortie .= $msg;
    }
  }
}

if (count($liste_id) == 1) {
  $retour= WEBSITE_SSL . "/modules/actes/actes_transac_show.php?id=" . $liste_id[0];
} else {
  $retour = WEBSITE_SSL . "/modules/actes/index.php";
}
  
Helpers::returnAndExit($status, $sortie, $retour);
