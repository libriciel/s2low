<?php

require_once("../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');
require_once(SITEROOT . '/class/X509Certificate.class.php');


$x509Certificate = new X509Certificate();


$me = new User();

if (! $me->authenticate()) {
  $_SESSION["error"] = "Échec de l'authentification";
  header("Location: " . WEBSITE);
  exit();
}

if (! $me->isAdmin()) {
  $_SESSION["error"] = "Accés refusé";
  header("Location: " . WEBSITE_SSL);
  exit();
}

$id = isset($_GET["id"]) ? $_GET["id"] : null;

$myAuthority = new Authority($me->get("authority_id"));

// Mode modification ou pas
$mod = false;
$him = new User();

$modStr = "Ajout";
if (isset($id) && ! empty($id)) {
  $him->setId($id);
  if ($him->init()) {
    $modStr = "Modification";
    $mod = true;
  } else {
    $him = new User();
  }
}

$new_id = Helpers::getVarFromGet('new_id'); 
if ($new_id){
	$him->setId($new_id);
	$him->init();
	$him->setId(null);
	$him->set("login","");
	$mod = true;
}

if (! $me->isSuper() && $mod) {
	if ($id) {
		$canUserEdit = $me->canEditUser($id);
	} else {
		$canUserEdit = $me->canEditUser($new_id);
	}
  if (! $canUserEdit) {
	$_SESSION["error"] = "Impossible de modifier cet utilisateur. Accés refusé.";
	header("Location: " . WEBSITE_SSL . "/admin/users/admin_users.php");
	exit();
  }
}

$doc = new HTMLLayout();

$doc->addHeader("<script src=\"" . WEBSITE_SSL . "/javascript/validateform.js\" type=\"text/javascript\"></script>\n");

$doc->setTitle("Tedetis : " . $modStr . " d'un utilisateur");


$doc->buildMenu($me);

$html = "<div id=\"content\">\n";
$html .= "<h1>Gestion des utilisateurs";

if ($me->isAuthorityAdmin()) {
  $html .= " de la collectivité «&nbsp;" . $myAuthority->get("name") . "&nbsp;»";
} elseif ($me->isGroupAdmin()) {
  $myGroup = new Group($me->get("authority_group_id"));
  $html .= " du groupe «&nbsp;" . $myGroup->get("name") . "&nbsp;»";
}

$html .= "</h1>\n";
$html .= "<center><a href=\"admin_users.php\" class=\"bouton\">Retour liste utilisateurs</a></center>\n";
$html .= "<h2>" . $modStr . " d'un utilisateur</h2>\n";
$html .= "<form action=\"admin_user_edit_handler.php\" method=\"post\" name=\"form\" enctype=\"multipart/form-data\" onsubmit=\"javascript:return validateForm(" . $him->getValidationTrio('name', 'givenname', 'email', 'authority_id', 'role', 'status');

if (! $mod) {
  $html .= ", 'certificate', 'Certificat utilisateur', 'RisString'";
}

$html .= ");\">\n";

if ($mod) {
	if ($new_id){
		$html .= "<input type=\"hidden\" name=\"new_id\" value=\"$new_id\" />\n";
		$html .= "<input type=\"hidden\" name=\"mode\" value=\"new_id\" />\n";
	} else {
  		$html .= "<input type=\"hidden\" name=\"id\" value=\"" . $him->getId() . "\" />\n";
  		$html .= "<input type=\"hidden\" name=\"mode\" value=\"modify\" />\n";
	}
} else {
  $html .= "<input type=\"hidden\" name=\"mode\" value=\"create\" />\n";
}



$html .= "<div class=\"data_table\">\n";
$html .= "<table class=\"data\">\n";
$html .= " <tr>\n";
$html .= "  <td class=\"td-register\">Nom&nbsp;:</td>\n";
$html .= "  <td class=\"td-input\"><input type=\"text\" name=\"name\" value=\"";
$html .= ($val = Helpers::getFromSession("name")) ? htmlspecialchars($val) : htmlspecialchars($him->get("name"));
$html .= "\" size=\"30\" maxlength=\"60\" /></td>\n";
$html .= " </tr>\n";
$html .= " <tr>\n";
$html .= "  <td class=\"td-register\">Pr&eacute;nom&nbsp;:</td>\n";
$html .= "  <td class=\"td-input\"><input type=\"text\" name=\"givenname\" value=\"";
$html .= ($val = Helpers::getFromSession("givenname")) ? htmlspecialchars($val) : htmlspecialchars($him->get("givenname"));
$html .= "\" size=\"30\" maxlength=\"60\" /></td>\n";
$html .= " </tr>\n";

$html .= " <tr>\n";
$html .= "  <td class=\"td-register\">Login*&nbsp;:</td>\n";
$html .= "  <td class=\"td-input\"><input type=\"text\" name=\"login\" value=\"";
$html .= ($val = Helpers::getFromSession("login")) ? htmlspecialchars($val) : htmlspecialchars($him->get("login"));
$html .= "\" size=\"30\" maxlength=\"60\" /></td>\n";
$html .= " </tr>\n";
$html .= " <tr>\n";
$html .= "  <td class=\"td-register\">Mot de passe*&nbsp;:</td>\n";
$html .= "  <td class=\"td-input\"><input type=\"password\" name=\"password\" value=\"\" size=\"30\" maxlength=\"60\" /></td>\n";
$html .= " </tr>\n";
$html .= " <tr>\n";
$html .= "  <td class=\"td-register\">Mot de passe (à nouveau)*&nbsp;:</td>\n";
$html .= "  <td class=\"td-input\"><input type=\"password\" name=\"password2\" value=\"\" size=\"30\" maxlength=\"60\" /></td>\n";
$html .= " </tr>\n";
$html .= "<tr><td>*uniquement nécessaire si deux utilisateurs ont le même certificat</td></tr>";

$html .= " <tr>\n";
$html .= "  <td class=\"td-register\">Adresse électronique&nbsp;:</td>\n";
$html .= "  <td class=\"td-input\"><input type=\"text\" name=\"email\" value=\"";
$html .= ($val = Helpers::getFromSession("email")) ? htmlspecialchars($val) : htmlspecialchars($him->get("email"));
$html .= "\" size=\"30\" maxlength=\"60\" /></td>\n";
$html .= " </tr>\n";
$html .= " <tr>\n";
$html .= "  <td class=\"td-register\">Téléphone&nbsp;:</td>\n";
$html .= "  <td class=\"td-input\"><input type=\"text\" name=\"telephone\" value=\"";
$html .= ($val = Helpers::getFromSession("telephone")) ? htmlspecialchars($val) : htmlspecialchars($him->get("telephone"));
$html .= "\" size=\"30\" maxlength=\"60\" /></td>\n";
$html .= " </tr>\n";
$html .= " <tr>\n";
$html .= "  <td class=\"td-register\">Importer le certificat utilisateur (format PEM)&nbsp;:</td>\n";
$html .= "  <td class=\"td-input\"><input type=\"file\" name=\"certificate\" /><br/>". 
htmlspecialchars($him->get('subject_dn')) . "<br/>\n";

$html .= "Expire le " .date("d/m/Y H:m:s",strtotime($x509Certificate->getExpirationDate($him->get('certificate'))));
$html .= "</td>";
$html .= " </tr>\n";
$html .= " <tr>\n";
$html .= "  <td class=\"td-register\">État&nbsp;:</td>\n";
$html .= "  <td class=\"td-input\">\n";
$status = ($val = Helpers::getFromSession("status")) ? $val : $him->get("status");

$html .= $doc->getHTMLSelect("status", $me->get("statusTypes"), $status);

$html .= "  </td>\n";
$html .= " </tr>\n";

if ($me->isGroupAdminOrSuper()) {
  if ($me->isGroupAdmin()) {
	$cond = " WHERE authorities.authority_group_id=" . $me->get("authority_group_id")." ORDER BY authorities.name ASC";
  } else {
	$cond = " ORDER BY authorities.name ASC";
  }

  $authorities = Authority::getAuthoritiesIdName($cond);

  $html .= " <tr>\n";
  $html .= "  <td class=\"td-register\">Collectivité&nbsp;:</td>\n";
  $html .= "  <td class=\"td-input\">\n";

  if (! $mod || $new_id) {
	$auth = ($val = Helpers::getFromSession("authority_id")) ? $val : $him->get("authority_id");

	$html .= $doc->getHTMLSelect("authority_id", $authorities, $auth);
  } else {
	$html .= $authorities[$him->get("authority_id")];
  }

  $html .= "  </td>\n";
  $html .= " </tr>\n";
}

$html .= " <tr> \n";
$html .= "  <td class=\"td-register\">Rôle&nbsp;:</td>\n";
$html .= "  <td class=\"td-input\">\n";

$roles = $me->get("roleTypes");

$hisRole = ($val = Helpers::getFromSession("role")) ? $val : $him->get("role");

if (! $me->isSuper()) {
  // Les admin simple et de groupe ne peut pas créer un super admin ni un admin de groupe
  $tmp = array();

  foreach ($roles as $role => $descr) {
	if ($role != 'SADM' && $role != 'GADM') {
	  $tmp[$role] = $descr;
	}
  }

  $roles = $tmp;
}

$html .= $doc->getHTMLSelect("role", $roles, $hisRole);

$html .= "  </td>\n";
$html .= " </tr>\n";

if ($me->isSuper()) {
  $html .= " <tr>\n";
  $html .= "  <td class=\"td-register\">Groupe (pour un administrateur de groupe)&nbsp;:</td>\n";
  $html .= "  <td class=\"td-input\">";

  $groups = Group::getGroupsIdName();

  $html .= $doc->getHTMLSelect("authority_group_id", $groups, $him->get("authority_group_id"));
  $html .= "</td>\n";
  $html .= " </tr>\n";
}

// Récupération des modules actifs globalement
$modules = Module::getActiveModulesList();

// Récupération des modules authorisés pour la collectivité
$authModules = array();
if ($mod) {
  $authModules = Module::getModulesForAuthority($him->get("authority_id"));
} else {
  if ($me->isGroupAdminOrSuper()) {
	// On ne sait pas à l'avance à quelle collectivité appartiendra l'utilisateur
	foreach ($modules as $module) {
	  if ($me->isGroupAdmin()) {
		if ($me->canGrantModule($module["name"])) {
		  $authModules[$module["id"]] = true;
		}
	  } else {
		$authModules[$module["id"]] = true;
	  }
	}
  } else {
	$authModules = $myAuthority->getAuthorizedModules();
  }
}


if (count($modules) > 0) {
  $moduleHtml = "";

  $perms = $me->get("permsTypes");

  reset($modules);
  foreach ($modules as $module) {
	if ($me->isSuper() || $authModules[$module["id"]]) {
	  $class = "";
	  if ($me->isSuper() && ! isset($authModules[$module["id"]])) {
		$class = " class=\"inactive\"";
	  }

	  $moduleHtml .= "    <dt" . $class . ">" . $module["description"] . "&nbsp;:</dt>";
	  $moduleHtml .= "    <dd" . $class . ">" . $doc->getHTMLSelect("perm_" . $module["id"], $me->get("permsTypes"), $him->getPerm($module["name"])) . "</dd>\n";
	}
  }

  if (! empty($moduleHtml)) {
	$html .= " <tr>\n";
	$html .= "  <td class=\"td-register\">Permissions modules&nbsp;:</td>\n";
	$html .= "  <td class=\"td-input\">\n";
	$html .= "   <dl>\n";
	$html .= $moduleHtml;
	$html .= "   </dl>\n";
	$html .= "  </td>\n";
	$html .= " </tr>\n";
  }
}

$html .= "</table>\n";
$html .= "</div>\n";
$html .= "<center><input type=\"submit\" class=\"submit_button\" value=\"";
$html .= ($mod) ? "Valider les modifications" : "Ajouter l'utilisateur";
$html .= "\" /></center>\n";
$html .= "</form>\n";

$ids_cert = $him->getIdFromCertData($him->get("subject_dn"),$him->get("issuer_dn"));

$html .= "<h2>Autre rôle de l'utilisateur</h2>";
if (count($ids_cert) > 1){
	$html .= "<div class=\"data_table\">\n";
	$html .= "<table cellpadding=\"3\" cellspacing=\"2\" class=\"data\">";
	$html .= "<tr>\n";
	$html .= " <th class=\"data\">Login</th>\n";
	$html .= " <th class=\"data\">Nom</th>\n";
	$html .= " <th class=\"data\">Adresse électronique</th>\n";
	$html .= " <th class=\"data\">R&ocirc;le</th>\n";
	$html .= " <th class=\"data\">État</th>\n";
	$html .= " <th class=\"data\">Collectivit&eacute;</th>\n";
	$html .= " <th class=\"data\">Actions</th>\n";
	$html .= "</tr>\n";
	
	$statusList = $me->get("statusTypes");
	$rolesList = $me->get("roleTypes");
	
	$i = 0;
	
	foreach($ids_cert as $id_other){
		if ($id_other == $him->getId()){
			continue;
		}
		$he = new User($id_other);
		$he->init();
		$he_authority = new Authority($he->get("authority_id"));
 		$html .= "<tr class=\"alternate" . ($i + 1) . "\">\n";
 		$html .= " <td>" . $he->get("login") . "</td>\n"; 		
  		$html .= " <td>" . $he->get("givenname") . " " . $he->get("name") . "</td>\n";
  		$html .= " <td><a href=\"mailto:" .$he->get("email"). "\">" . $he->get("email") . "</a></td>\n";
  		$html .= " <td>" . $rolesList[$he->get("role")] . "</td>\n";
  		$html .= " <td>" . $statusList[$he->get("status")] . "</td>\n";
  		$html .= " <td><a href=\"" . WEBSITE_SSL . "/admin/authorities/admin_authority_edit.php?id=" . $he->get("authority_id"). "\">" . $he_authority->get("name")  . "</a></td>\n";
  		$html .= " <td><a href=\"" . WEBSITE_SSL . "/admin/users/admin_user_edit.php?id=" .  $he->get("id") . "\" class=\"icon\"><img src=\"" . WEBSITE_SSL . "/custom/images/erreur.png\" alt=\"image_modif\" title=\"Modifier\" /></a></td>\n";
  		$html .= "</tr>\n";
  		 $i = ($i + 1) % 2;
	}
	$html .= '</table>';
}

if ($him->get('login')) {
	$html .="<a href='admin_user_edit.php?new_id=".($id?$id:$new_id)."'>Créer un nouveau rôle avec le même certificat </a>";
	if ($him->get('subject_dn')){
		$html .= "(".htmlspecialchars($him->get('subject_dn')) .")";	
	}
} else {
	$html .= "Si vous voulez créer un autre utilisateur a partir du même certificat, vous devez saisir le champ login";
}

$serviceUser = new ServiceUser(DatabasePool::getInstance());
$services = $serviceUser->getServiceUser($him->get('authority_id'));

if ($id && $services){
	$html .= "<h2>Services</h2>";
	
	$userService = $serviceUser->getServiceFromUser($him->getId());
	
	if ($userService) {
		$html .= "Cet utilisateur fait partie des services : ";
		foreach($userService as $i => $s){
			if ($i != 0){
				$html .= ", ";
			}
			$html .= "<a href='../services/gestion-service-content.php?id=".$s['id']."'>".$s['name']."</a>";
		}
		
	} else {
		$html .= "Cet utilisateur ne fait partie d'aucun service";
	}
	$html .="<br/><br/>";

	$html .= "<form action='add-user-to-service.php' method='post'>";
	$html .= "<input type='hidden' name='id_user' value='".$him->getId()."'>";
	$html .= " Mettre dans le service : <select name='id_service'>";
	foreach($services as $s){
		$html .= "<option value='".$s['id']."'>" . $s['name'] . "</option>";	
	}
	$html .= "<input type='submit' value='ajouter'>";
	$html .= "</form>";
}

$html .= "</div>\n"; //Content...
$doc->addBody($html);

$doc->buildFooter();

$doc->display();

?>
