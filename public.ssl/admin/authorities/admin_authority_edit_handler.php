<?php

require_once("../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');
require_once( SITEROOT . '/class/Mailer.class.php');

$me = new User();

$api = Helpers::getVarFromPost("api");


function exitOrDisplayError($api,$erreur_msg,$location){
	if ($api){
		$jsonOutput = new JSONoutput();
		$jsonOutput->displayErrorAndExit($erreur_msg);
	} else {
		$_SESSION["error"] = $erreur_msg;
		header("Location: $location" );
		exit;
	}
}

if (! $me->authenticate()) {
	exitOrDisplayError($api,"Échec de l'authentification",WEBSITE);
}

if (! $me->isAdmin()) {
	exitOrDisplayError($api,"Accès refusé",WEBSITE_SSL);
}

$id = Helpers::getVarFromPost("id");
$name = Helpers::getVarFromPost("name");
$siren = Helpers::getVarFromPost("siren");
$authorityGroupId = Helpers::getVarFromPost("authority_group_id");
$agreement = Helpers::getVarFromPost("agreement");
$email = Helpers::getVarFromPost("email");
$defaultbroadcastEmail = Helpers::getVarFromPost("default_broadcast_email");
$broadcastEmail = Helpers::getVarFromPost("broadcast_email");
$status = Helpers::getVarFromPost("status");
$authorityTypeId = Helpers::getVarFromPost("authority_type_id");
$address = Helpers::getVarFromPost("address");
$postalCode = Helpers::getVarFromPost("postal_code");
$city = Helpers::getVarFromPost("city");
$department = Helpers::getVarFromPost("department");
$district = Helpers::getVarFromPost("district");
$telephone = Helpers::getVarFromPost("telephone");
$fax = Helpers::getVarFromPost("fax");
$ext_siret=Helpers::getVarFromPost("ext_siret");
$helios_ftp_login=Helpers::getVarFromPost("helios_ftp_login");
$helios_ftp_password=Helpers::getVarFromPost("helios_ftp_password");
$helios_ftp_dest=Helpers::getVarFromPost("helios_ftp_dest");
$email_mail_securise = Helpers::getVarFromPost("email_mail_securise");
$newmailnotif = Helpers::getVarFromPost("newnotif");
$dia_siret = Helpers::getVarFromPost("dia_siret");

if($newmailnotif == 'on')
    $newmailnotif='true';
else
    $newmailnotif='false';



$form_location =  WEBSITE_SSL . "/admin/authorities/admin_authority_edit.php?id=$id"; 


$sqlQuery = new SQLQuery(DB_DATABASE);
$sqlQuery->setDatabaseHost(DB_HOST);
$sqlQuery->setCredential(DB_USER,DB_PASSWORD);
$authoritySQL = new AuthoritySQL($sqlQuery);

if (! $authoritySQL->verifDepartmentAndDistrict($department, $district)){
	exitOrDisplayError($api,"Le code département ou le code arrondissement sont incorrects",$form_location);
	
}


$authority = new Authority();
$mod = false;



if (isset($id) && ! empty($id)) {
  $authority->setId($id);
  $mod = true;
  if (! $authority->init()) {
	exitOrDisplayError($api,"Erreur lors de la modification de la collectivité",$form_location);
  }
  $form_location =  WEBSITE_SSL . "/admin/authorities/admin_authority_edit.php?id=$id"; 
}




// Mode ajout => interdit aux admins simples
// et modif de sa collectivité uniquement
if (! $me->isGroupAdminOrSuper()) {
  if ($authority->isNew() || $authority->getId() != $me->get("authority_id")) {
  	exitOrDisplayError($api,"Accès refusé",$form_location);
  }
} elseif ($me->isGroupAdmin()) {
  // Si mode modif on vérifie que la collectivité appartient bien au groupe dont l'utilisateur est admin
  if (! $authority->isNew() && ! $authority->isInGroup($me->get("authority_group_id"))) {
  	exitOrDisplayError($api,"Accès refusé.",$form_location);
  }

  // Vérification que le SIREN est bien autorisé pour ce groupe
  $group = new Group($me->get("authority_group_id"));

  $sirenList = $group->getAuthorizedSiren();

  if (array_search($siren, $sirenList) === false) {
  	exitOrDisplayError($api,"Ce numéro de SIREN (" . $siren . ") n'est pas autorisé pour le groupe " . $group->get("name"),$form_location);
  }

  // On force le authority_group_id à celui de l'admin du groupe
  $authorityGroupId = $me->get("authority_group_id");
}


//Vérification de l'email de la collectivité pour le module mail sec
$mailer = new Mailer();
if ($email_mail_securise && (  ! $mailer->isValidMail($email_mail_securise) || strstr($email_mail_securise," ")) ) {
 	if ($authority->isNew()) {
 		$location = WEBSITE_SSL . "/admin/authorities/admin_authorities.php";
 	} else {
		$location = WEBSITE_SSL . "/admin/authorities/admin_authority_edit.php?id=" . $authority->getId();
 	}  	
 	exitOrDisplayError($api,"L'email " . get_hecho($email_mail_securise) . " n'est pas valide.",$location);
}


if ($me->isGroupAdminOrSuper()) {
  $authority->set("name", $name);
  $authority->set("helios_ftp_login", $helios_ftp_login);
  $authority->set("helios_ftp_password", $helios_ftp_password);
  $authority->set("siren", $siren);
  $authority->set("ext_siret",$ext_siret);
  $authority->set("authority_group_id", $authorityGroupId);
  $authority->set("agreement", $agreement);
  $authority->set("status", $status);
  $authority->set("authority_type_id", $authorityTypeId);
  $authority->set("department", $department);
  $authority->set("district", $district);
  $authority->set("helios_ftp_dest",$helios_ftp_dest);
}

$authority->set("email", $email);
$authority->set("default_broadcast_email", $defaultbroadcastEmail);
$authority->set("broadcast_email", $broadcastEmail);
$authority->set("address", $address);
$authority->set("postal_code", $postalCode);
$authority->set("city", $city);
$authority->set("telephone", $telephone);
$authority->set("fax", $fax);
$authority->set("email_mail_securise",$email_mail_securise);
$authority->set("new_notification",$newmailnotif);
$authority->set("dia_siret",$dia_siret);

$savePerms = false;
if ($me->isGroupAdminOrSuper()) {
  $savePerms = true;
  // Module autorisés pour la collectivité
  $modules = Module::getActiveModulesList();
  $authority->resetModulesPerms();

  foreach ($modules as $module) {
	$authority->setModulePerm($module["id"], Helpers::getVarFromPost("perm_" . $module["id"]));
  }
}

if (! $authority->save($savePerms)) {
  $msg = "Erreur lors de l'enregistrement de la collectivité&nbsp;:\n" . $authority->getErrorMsg();
  if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 3,false, $me->get("role"), false, $me)) {
	$msg .= "\nErreur de journalisation.";
  }

  if ($authority->isNew()) {
	$location = WEBSITE_SSL . "/admin/authorities/admin_authorities.php";
  } else {
	$location =  WEBSITE_SSL . "/admin/authorities/admin_authority_edit.php?id=" . $authority->getId();
  }  
  
  exitOrDisplayError($api, nl2br($msg),$location);

}

$msg = ($mod) ? "Modification" : "Création";
$msg .= " de la collectivité " . $authority->get("name") . " (id=" . $authority->getId() . "). Résultat ok.";
if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, $me->get("role"), false, $me)) {
	$msg .= "\nErreur de journalisation.";
}

if ($api){
	$jsonOutput = new JSONoutput();
	$jsonOutput->display(array('status'=>'ok','message'=>$msg,'id'=>$authority->getId()));
} else {
	$_SESSION["error"] = nl2br($msg);
	header("Location: ". WEBSITE_SSL . "/admin/authorities/admin_authority_edit.php?id=" . $authority->getId() );
	exit;
}
