<?php
require_once("../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');

$me = new User();

$api = Helpers::getVarFromPost("api");

function exitOrDisplayError($api,$erreur_msg,$location){
	if ($api){
		$jsonOutput = new JSONoutput();
		$jsonOutput->displayErrorAndExit($erreur_msg);
	} else {
		$_SESSION["error"] = $erreur_msg;
		header("Location: $location " );
		exit;
	}
}

if (! $me->authenticate()) {
	exitOrDisplayError($api,"Échec de l'authentification",WEBSITE);
}

if (! $me->isAdmin()) {
	exitOrDisplayError($api,"Accès refusé",WEBSITE_SSL);
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
  	exitOrDisplayError($api,"Erreur lors de la modification de l'utilisateur",WEBSITE_SSL . "/admin/users/admin_users.php");
  } else {
	// On vérifie que l'utilisateur courant à le droit de modifier cet utilisateur
	if (! $me->canEditUser($id)) {
		exitOrDisplayError($api,"Accès refusé pour la modification de cet utilisateur", WEBSITE_SSL . "/admin/users/admin_users.php");
	}
	$mod = true;
  }
}

if ($role == 'GADM' && ! $authority_group_id){
	exitOrDisplayError($api,"Vous devez indiquez un groupe pour créer un administrateur de groupe", WEBSITE_SSL . "/admin/users/admin_user_edit.php". "?id=" . $him->getId() ."&new_id=$new_id");
}

if (! $api && $password != $password2){
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
  	exitOrDisplayError($api,"Le certificat utilisateur est obligatoire", WEBSITE_SSL . "/admin/users/admin_users.php");	
  }
}

if ($new_id){	
	if (! $password || ! $login){
		exitOrDisplayError($api, "Le login et le mot de passe sont obligatoire pour cloner un certificat", WEBSITE_SSL ."/admin/users/admin_user_edit.php?new_id=$new_id");	
	}
	if ($him->getIdFromLogin($login)){
		exitOrDisplayError($api, "Ce login est déja utilisé", WEBSITE_SSL ."/admin/users/admin_user_edit.php?new_id=$new_id");				
	}
	$him->cloneCertificat($new_id);
} else {
	if ($login){
		
		$the_id = $him->getIdFromLogin($login);
		
		if ($the_id && $id != $the_id){
			exitOrDisplayError($api, "Ce login est déja utilisé", WEBSITE_SSL ."/admin/users/admin_users.php");
		}
	}
}



if (! $mod || $new_id) {
	
  if ($me->isSuper()) {
	$him->set("authority_id", $authority_id);
	
  } elseif ($me->isGroupAdmin()) { 
	$authority = new Authority($authority_id);

	if ($authority->isInGroup($me->get("authority_group_id"))) {
	  $him->set("authority_id", $authority_id);
	} else {
		exitOrDisplayError($api, "La collectivité n'appartient pas au groupe courant", 
		WEBSITE_SSL ."/admin/users/admin_users.php");
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
  if (isset($authModules[$module["id"]]) && ($authModules[$module["id"]] || $him->isGroupAdmin())) {
	$him->setPerm($module["id"], Helpers::getVarFromPost("perm_" . $module["id"]),$module['specific_perms']);
  }
}

if (! $him->save()) {
  $msg = "Erreur lors de l'enregistrement de l'utilisateur :\n" . $him->getErrorMsg();
  if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 3, false, $me->get("role"), false, $me)) {
	$msg .= "\nErreur de journalisation.";
  }


  if ($him->isNew()) {
	$location =  WEBSITE_SSL . "/admin/users/admin_user_edit.php?new_id=$new_id";
  } else {
	Helpers::purgeTempSession();
	$location = WEBSITE_SSL . "/admin/users/admin_user_edit.php?id=" . $him->getId()."&new_id=$new_id";
  }
  
  exitOrDisplayError($api, nl2br($msg), $location);
  
} 

$msg = ($mod) ? "Modification" : "Création";
$msg .= " de l'utilisateur " . $him->getPrettyName() . " (id=" . $him->getId() . "). Résultat ok.";
if (! Log::newEntry(LOG_ISSUER_NAME, $msg, 1, false, $me->get("role"), false, $me)) {
	$msg .= "\nErreur de journalisation.";
}


if ($api){
	$jsonOutput = new JSONoutput();
	$jsonOutput->display(array('status'=>'ok','message'=>$msg,'id'=>$him->getId()));
} else {
	$_SESSION["error"] = nl2br($msg);
	Helpers::purgeTempSession();
	header("Location: " . WEBSITE_SSL . "/admin/users/admin_user_edit.php?id=" . $him->getId() );
}

