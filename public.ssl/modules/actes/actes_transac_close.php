<?php
require_once( __DIR__ . "/../../../init/init-www-actes.php");

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

if (!$module->isActive() || $me->isGroupAdminOrSuper() || !$me->checkDroit($module->get("name"),'CS')) {
  Helpers::returnAndExit(1, "Accès refusé", WEBSITE_SSL);
}

$liste_id = array ();

if (Helpers::getVarFromPost("id") != null) {
  $liste_id[] = Helpers::getVarFromPost("id");
} else {
  $liste_id = Helpers::getVarFromPost("liste_id");
}

$status = Helpers::getVarFromPost("status");
$types = ActesTransaction::getStatusList();
$myAuthority = new Authority($me->get("authority_id"));

if ($status == "valid") {
	$new_status_id = 5;
} elseif ($status == "invalid") {
	$new_status_id = 6;
} elseif ($status == "sae"){
	$new_status_id = 19;
	$actesArchiveControler = new ActesArchiveControler($sqlQuery);
	
} else {
	Helpers::returnAndExit(1, "État incorrect.", WEBSITE_SSL . "/modules/actes/index.php");
}
    
foreach ($liste_id as $id) {
    $trans = new ActesTransaction();
	$trans->setId($id);
	if ($trans->init()) {
		$owner = new User($trans->get("user_id"));
		$owner->init();
	} else {
		Helpers::returnAndExit(1, "Erreur d'initialisation de la transaction.", WEBSITE_SSL . "/modules/actes/index.php");
	}

    if ($trans->get("type") != 1) {
      Helpers::returnAndExit(1, "Ce type de transaction ne peut pas être cloturé.", WEBSITE_SSL . "/modules/actes/actes_transac_show.php?id=" . $rel_trans->getId());
    }

    if (! $trans->canValidate() && ! ACTES_ALWAYS_CAN_VALIDATE){
        $sortie .= "Cette transaction $id ne peut pas encore être clôturé\n";
        continue;
    }
    $envelope = new ActesEnvelope($trans->get("envelope_id"));
    $envelope->init();

    // Vérification des permissions
    if (!($me->isAdmin() && $me->get("authority_id") == $owner->get("authority_id")) && !($me->getId() == $envelope->get("user_id") && $me->checkDroit($module->get("name"),'CS'))) {
      Helpers::returnAndExit(1, "Accès refusé.", WEBSITE_SSL . "/modules/actes/index.php");
    }
    
    if ($new_status_id == 19) {
    	$result = $actesArchiveControler->setArchiveEnAttenteEnvoiSEA($me->getId(),$id);
		if ($result){
			$msg = "Programmation de l'envoi de la transaction $id à Pastell\n";
	    	$severity = 1;
	      	$status = 0;	
		} else {
			$msg= "Erreur lors de l'envoi de la transaction $id à Pastell : " . $actesArchiveControler->getLastError();
			$severity = 3;
			$status = 1;
		}
    } else if (! $trans->setNewStatus($new_status_id, "Fermeture par l'utilisateur " . $me->getPrettyName())) {
      $msg = "Erreur lors de la tentative de passage de la transaction n°" . $trans->getId() . " vers l'état " . $types[$new_status_id] . ".\n";
      $severity = 3;
      $status = 1;
    } else {
      $msg = "Passage de la transaction n°" . $trans->getId() . " à l'état « " . $types[$new_status_id] . " ». Résultat ok.\n";
      $severity = 1;
      $status = 0;
    }
    
  	$sortie .= $msg;
    if (! Log::newEntry(LOG_ISSUER_NAME, $msg, $severity, false, 'USER', $module->get("name"), $me)) {
      $sortie .= "\nErreur de journalisation.\n";
    }
}

if (count($liste_id) == 1) {
  $retour= WEBSITE_SSL . "/modules/actes/actes_transac_show.php?id=" . $liste_id[0];
} else {
  $retour = WEBSITE_SSL . "/modules/actes/index.php";
}
  
Helpers::returnAndExit($status, $sortie, $retour);
