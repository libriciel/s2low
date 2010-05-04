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
 * \file admin_user_edit_handler.php
 * \brief Page de traitement des modifications ou ajout d'utilisateur
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 15.02.2006
 * 
 *
 * Cette page effectue le traitement d'ajout ou de modification d'un
 * utilisateur dans la base de données
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *  JS   17.07.2006  Adaptation pour Tedetis
 */

// Configuration
require_once("../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');

$me = new User();

if (! $me->authenticate()) {
  $_SESSION["error"] = "Échec de l'authentification";
  header("Location: " . WEBSITE);
  exit();
}

if (! $me->isAdmin()) {
  $_SESSION["error"] = "Accès refusé";
  header("Location: " . WEBSITE_SSL);
  exit();
}

$myAuthority = new Authority($me->get("authority_id"));

// Récupération des variables du POST
$id = Helpers::getVarFromPost("id");
$name = Helpers::getVarFromPost("name", true);
$givenname = Helpers::getVarFromPost("givenname", true);
$email = Helpers::getVarFromPost("email", true);
$telephone = Helpers::getVarFromPost("telephone", true);
$status = Helpers::getVarFromPost("status", true);
$authority_id = Helpers::getVarFromPost("authority_id", true);
$role = Helpers::getVarFromPost("role", true);
$authority_group_id = Helpers::getVarFromPost("authority_group_id", true);
$certificate = $_FILES["certificate"];

$login = Helpers::getVarFromPost("login", true);
$password = Helpers::getVarFromPost("password", true);
$password2 = Helpers::getVarFromPost("password2", true);

$new_id = Helpers::getVarFromPost("new_id", true);

$him = new User();
$mod = false;

if (isset($id) && ! empty($id)) {
  $him->setId($id);
  if (! $him->init()) {
    $_SESSION["error"] = "Erreur lors de la modification de l'utilisateur";
    header("Location: " . WEBSITE_SSL . "/admin/users/admin_users.php");
    exit();
  } else {
	// On vérifie que l'utilisateur courant à le droit de modifier cet utilisateur
	if (! $me->canEditUser($id)) {
	  $_SESSION["error"] = "Accès refusé pour la modification de cet utilisateur";
	  header("Location: " . WEBSITE_SSL . "/admin/users/admin_users.php");
	  exit();
	}

	$mod = true;
  }
}

if ($password != $password2){
	  $_SESSION["error"] = "Les mots de passe ne correspondent pas";
	  header("Location: " . WEBSITE_SSL . "/admin/users/admin_user_edit.php". "?id=" . $him->getId() ."&new_id=$new_id");
	  exit();
}

$him->set("name", $name);
$him->set("givenname", $givenname);
$him->set("email", $email);
$him->set("telephone", $telephone);
$him->set("status", $status);
$him->set("login",$login);
if ($password){
	$him->set("password",md5($password));
}

// Le groupe d'appartenance pour un administrateur de groupe
if ($me->isSuper()) {
  $him->set("authority_group_id", $authority_group_id);
}

// Traitement du certificat
if (is_array($certificate) && count($certificate) > 0 && is_uploaded_file($certificate["tmp_name"])) {
  $him->set("certFilePath", $certificate["tmp_name"]);
} else {
  if (! $mod && ! $new_id) {
	$_SESSION["error"] = "Le certificat utilisateur est obligatoire<br />";
	header("Location: " . WEBSITE_SSL . "/admin/users/admin_users.php");
	exit();
  }
}

if ($new_id){	
	if (! $password || ! $login){
		$_SESSION["error"] = "Le login et le mot de passe sont obligatoire pour cloner un certificat<br />";
		header("Location: " . WEBSITE_SSL . "/admin/users/admin_user_edit.php?new_id=$new_id");
		exit();
	}
	if ($him->getIdFromLogin($login)){
		$_SESSION["error"] = "Ce login est déja utilisé<br />";
		header("Location: " . WEBSITE_SSL . "/admin/users/admin_user_edit.php?new_id=$new_id");
		exit();
	}
	$him->cloneCertificat($new_id);
}



if (! $mod || $new_id) {
	
  if ($me->isSuper()) {
	$him->set("authority_id", $authority_id);
	
  } elseif ($me->isGroupAdmin()) { 
	$authority = new Authority($authority_id);

	if ($authority->isInGroup($me->get("authority_group_id"))) {
	  $him->set("authority_id", $authority_id);
	} else {
	  $_SESSION["error"] = "La collectivité n'appartient pas au groupe courant<br />";
	  header("Location: " . WEBSITE_SSL . "/admin/users/admin_users.php");
	  exit();
	}
  } else {
	// Un admin de collectivité ne peut créer que des utilisateurs appartenant à sa collectivité
	$him->set("authority_id", $myAuthority->getId());
  }
}

// Les admins de collectivité et de groupe ne peuvent créer que des administrateurs de collectivité 
// ou des utilisateurs simples
if (! $me->isSuper()) {
  if (strcasecmp($role, 'SADM') == 0 || strcasecmp($role, 'GADM') == 0) {
	$role = 'ADM';
  }
}

$him->set("role", $role);

// Permissions sur les modules
// Récupération des modules actifs globalement
$modules = Module::getActiveModulesList();
// Récupération des modules authorisés pour la collectivité
$authModules = Module::getModulesForAuthority($him->get("authority_id"));

$him->resetPerms();

foreach ($modules as $module) {
  if ($authModules[$module["id"]] || $him->isGroupAdmin()) {
	$him->setPerm($module["id"], Helpers::getVarFromPost("perm_" . $module["id"]));
  }
}
//if (! $him->save($genNewCert)) {
if (! $him->save()) {
  $msg = "Erreur lors de l'enregistrement de l'utilisateur :\n" . $him->getErrorMsg();
  if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 3, false, $me->get("role"), false, $me)) {
	$msg .= "\nErreur de journalisation.";
  }

  $_SESSION["error"] = nl2br($msg);

  if ($him->isNew()) {
	header("Location: " . WEBSITE_SSL . "/admin/users/admin_user_edit.php?new_id=$new_id");
  } else {
	Helpers::purgeTempSession();
	header("Location: " . WEBSITE_SSL . "/admin/users/admin_user_edit.php?id=" . $him->getId()."&new_id=$new_id");
  }
  exit();
} else {
  $msg = ($mod) ? "Modification" : "Création";
  $msg .= " de l'utilisateur " . $him->getPrettyName() . " (id=" . $him->getId() . "). Résultat ok.";
  if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, $me->get("role"), false, $me)) {
	$msg .= "\nErreur de journalisation.";
  }

  $_SESSION["error"] = nl2br($msg);
  Helpers::purgeTempSession();
  header("Location: " . WEBSITE_SSL . "/admin/users/admin_user_edit.php?id=" . $him->getId() );
  exit();
}
