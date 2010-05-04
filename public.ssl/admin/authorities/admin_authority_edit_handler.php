<?php
/*
 * TéDéTIS - Copyright 2006 Alternance-Soft
 * Contributeur : Jérôme Schell, AoÃ»t 2006 
 *
 * contact@alternancesoft.com
 *
 * Ce logiciel est un programme informatique servant Ã  la
 * dÃ©matÃ©rialisation de l'administration. 
 *
 * Ce logiciel est rÃ©gi par la licence CeCILL soumise au droit franÃ§ais et
 * respectant les principes de diffusion des logiciels libres. Vous pouvez
 * utiliser, modifier et/ou redistribuer ce programme sous les conditions
 * de la licence CeCILL telle que diffusÃ©e par le CEA, le CNRS et l'INRIA 
 * sur le site "http://www.cecill.info".
 *
 * En contrepartie de l'accessibilitÃ© au code source et des droits de copie,
 * de modification et de redistribution accordÃ©s par cette licence, il n'est
 * offert aux utilisateurs qu'une garantie limitÃ©e.  Pour les mÃªmes raisons,
 * seule une responsabilitÃ© restreinte pÃ¨se sur l'auteur du programme,  le
 * titulaire des droits patrimoniaux et les concÃ©dants successifs.
 *
 * A cet Ã©gard  l'attention de l'utilisateur est attirÃ©e sur les risques
 * associÃ©s au chargement,  Ã  l'utilisation,  Ã  la modification et/ou au
 * dÃ©veloppement et Ã  la reproduction du logiciel par l'utilisateur Ã©tant 
 * donnÃ© sa spÃ©cificitÃ© de logiciel libre, qui peut le rendre complexe Ã  
 * manipuler et qui le rÃ©serve donc Ã  des dÃ©veloppeurs et des professionnels
 * avertis possÃ©dant  des  connaissances  informatiques approfondies.  Les
 * utilisateurs sont donc invitÃ©s Ã  charger  et  tester  l'adÃ©quation  du
 * logiciel Ã  leurs besoins dans des conditions permettant d'assurer la
 * sÃ©curitÃ© de leurs systÃ¨mes et ou de leurs donnÃ©es et, plus gÃ©nÃ©ralement, 
 * Ã l'utiliser et l'exploiter dans les mÃªmes conditions de sÃ©curitÃ©. 
 *
 * Le fait que vous puissiez accÃ©der Ã  cet en-tÃªte signifie que vous avez 
 * pris connaissance de la licence CeCILL, et que vous en avez acceptÃ© les
 * termes.
*/
?>
<?php
/**
 * \file admin_authority_edit_handler.php
 * \brief Page de traitement des modifications ou ajout de collectivité
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 17.03.2006
 * 
 *
 * Cette page effectue le traitement d'ajout ou de modification d'une
 * collectivité dans la base de données
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *  JS   21.07.2006  Adaptation pour Tedetis
 */

// Configuration
require_once("../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');
require_once( SITEROOT . '/class/Mailer.class.php');

$me = new User();

if (! $me->authenticate()) {
  $_SESSION["error"] = "ehec de l'authentification";
  header("Location: " . WEBSITE);
  exit();
}

if (! $me->isAdmin()) {
  $_SESSION["error"] = "Acces refuse";
  header("Location: " . WEBSITE_SSL);
  exit();
}

// Recuperation des variables du POST
$id = Helpers::getVarFromPost("id");
$mode = Helpers::getVarFromPost("mode");
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

$authority = new Authority();
$mod = false;

$form_location = "Location: " . WEBSITE_SSL . "/admin/authorities/admin_authority_edit.php";



if (isset($id) && ! empty($id)) {
  $authority->setId($id);
  $mod = true;
  if (! $authority->init()) {
    $_SESSION["error"] = "Erreur lors de la modification de la collectivité";
    header($form_location);
    exit;
  }
  
  $form_location = "Location: " . WEBSITE_SSL . "/admin/authorities/admin_authority_edit.php?id=$id";
  
}




// Mode ajout => interdit aux admins simples
// et modif de sa collectivité uniquement
if (! $me->isGroupAdminOrSuper()) {
  if ($authority->isNew() || $authority->getId() != $me->get("authority_id")) {
	$_SESSION["error"] = "Accès refusé.";
	header($form_location);
	exit();
  }
} elseif ($me->isGroupAdmin()) {
  // Si mode modif on vérifie que la collectivité appartient bien au groupe dont l'utilisateur est admin
  if (! $authority->isNew() && ! $authority->isInGroup($me->get("authority_group_id"))) {
	$_SESSION["error"] = "Accès refusé.";
	header($form_location);
	exit();
  }

  // Vérification que le SIREN est bien autorisé pour ce groupe
  $group = new Group($me->get("authority_group_id"));

  $sirenList = $group->getAuthorizedSiren();

  if (array_search($siren, $sirenList) === false) {
	$_SESSION["error"] = "Ce numéro de SIREN (" . $siren . ") n'est pas autorisé pour le groupe " . $group->get("name");
	header($form_location);
	exit();
  }

  // On force le authority_group_id à celui de l'admin du groupe
  $authorityGroupId = $me->get("authority_group_id");
}


//Vérification de l'email de la collectivité pour le module mail sec
$mailer = new Mailer();
if ($email_mail_securise && (  ! $mailer->isValidMail($email_mail_securise) || strstr($email_mail_securise," ")) ) {
	$_SESSION['error'] = "L'email " . htmlentities($email_mail_securise) . " n'est pas valide.";
 	if ($authority->isNew()) {
		header("Location: " . WEBSITE_SSL . "/admin/authorities/admin_authorities.php");
	} else {
		header("Location: " . WEBSITE_SSL . "/admin/authorities/admin_authority_edit.php?id=" . $authority->getId());
 	}  	
 	exit;
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

  $_SESSION["error"] = nl2br($msg);

  if ($authority->isNew()) {
	header("Location: " . WEBSITE_SSL . "/admin/authorities/admin_authorities.php");
  } else {
	header("Location: " . WEBSITE_SSL . "/admin/authorities/admin_authority_edit.php?id=" . $authority->getId());
  }  
  exit();
} else {
  $msg = ($mod) ? "Modification" : "Création";
  $msg .= " de la collectivité " . $authority->get("name") . " (id=" . $authority->getId() . "). Résultat ok.";
  if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, $me->get("role"), false, $me)) {
	$msg .= "\nErreur de journalisation.";
  }

  $_SESSION["error"] = nl2br($msg);
  header("Location: " . WEBSITE_SSL . "/admin/authorities/admin_authority_edit.php?id=" . $authority->getId());
}
