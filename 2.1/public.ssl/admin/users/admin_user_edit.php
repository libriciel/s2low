<?php

require_once( __DIR__ . "/../../../init/init.php");
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

$doc->openContainer();
$doc->openSideBar();
$doc->buildMenu($me);
$doc->closeSideBar();
$doc->openContent();

$html .= "<h1>Gestion des utilisateurs";

if ($me->isAuthorityAdmin()) {
  $html .= " de la collectivité «&nbsp;" . $myAuthority->get("name") . "&nbsp;»";
} elseif ($me->isGroupAdmin()) {
  $myGroup = new Group($me->get("authority_group_id"));
  $html .= " du groupe «&nbsp;" . $myGroup->get("name") . "&nbsp;»";
}

$html .= "</h1>\n";
$html .= "<p id=\"back-user-btn\"><a class=\"btn btn-default\" href=\"admin_users.php\">Retour liste utilisateurs</a></p>\n";
$html .= "<h2>" . $modStr . " d'un utilisateur</h2>\n";
$html .= "<form class=\"form form-horizontal\" action=\"admin_user_edit_handler.php\" method=\"post\" name=\"form\" enctype=\"multipart/form-data\" onsubmit=\"javascript:return validateForm(" . $him->getValidationTrio('name', 'givenname', 'email', 'authority_id', 'role', 'status');

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

$html .= "<div class=\"alert alert-info\"><span class=\"mandatory\">*</span> uniquement nécessaire si deux utilisateurs ont le même certificat</div>";

$html .= " <div class=\"form-group\">\n";
$html .= "  <label class=\"control-label col-md-4\">Nom : </label>\n";
$html .= "  <div class=\"col-md-6 \"><input class=\"form-control\" type=\"text\" name=\"name\" value=\"";
$html .= ($val = Helpers::getFromSession("name")) ? get_hecho($val) : get_hecho($him->get("name"));
$html .= "\" size=\"30\" maxlength=\"60\" /></div>\n";
$html .= " </div>\n";
$html .= " <div class=\"form-group\">\n";
$html .= "  <label class=\"control-label col-md-4\">Pr&eacutenom : </label>\n";
$html .= "  <div class=\"col-md-6 \"><input class=\"form-control\" type=\"text\" name=\"givenname\" value=\"";
$html .= ($val = Helpers::getFromSession("givenname")) ? get_hecho($val) : get_hecho($him->get("givenname"));
$html .= "\" size=\"30\" maxlength=\"60\" /></div>\n";
$html .= " </div>\n";

$html .= " <div class=\"form-group\">\n";
$html .= "  <label class=\"control-label col-md-4\">Login <span class=\"mandatory\">*</span> :</label>\n";
$html .= "  <div class=\"col-md-6 \"><input class=\"form-control\" type=\"text\" name=\"login\" value=\"";
$html .= ($val = Helpers::getFromSession("login")) ? get_hecho($val) : get_hecho($him->get("login"));
$html .= "\" size=\"30\" maxlength=\"60\" /></div>\n";
$html .= " </div>\n";
$html .= " <div class=\"form-group\">\n";
$html .= "  <label class=\"control-label col-md-4\">Mot de passe <span class=\"mandatory\">*</span> :</label>\n";
$html .= "  <div class=\"col-md-6 \"><input class=\"form-control\" type=\"password\" name=\"password\" value=\"\" size=\"30\" maxlength=\"60\" /></div>\n";
$html .= " </div>\n";
$html .= " <div class=\"form-group\">\n";
$html .= "  <label class=\"control-label col-md-4\">Mot de passe (à nouveau) <span class=\"mandatory\">*</span> :</label>\n";
$html .= "  <div class=\"col-md-6 \"><input class=\"form-control\" type=\"password\" name=\"password2\" value=\"\" size=\"30\" maxlength=\"60\" /></div>\n";
$html .= " </div>\n";

$html .= " <div class=\"form-group\">\n";
$html .= "  <label class=\"control-label col-md-4\">Adresse électronique :</label>\n";
$html .= "  <div class=\"col-md-6 \"><input class=\"form-control\" type=\"text\" name=\"email\" value=\"";
$html .= ($val = Helpers::getFromSession("email")) ? get_hecho($val) : get_hecho($him->get("email"));
$html .= "\" size=\"30\" maxlength=\"60\" /></div>\n";
$html .= " </div>\n";
$html .= " <div class=\"form-group\">\n";
$html .= "  <label class=\"control-label col-md-4\">Téléphone :</label>\n";
$html .= "  <div class=\"col-md-6 \"><input class=\"form-control\" type=\"text\" name=\"telephone\" value=\"";
$html .= ($val = Helpers::getFromSession("telephone")) ? get_hecho($val) : get_hecho($him->get("telephone"));
$html .= "\" size=\"30\" maxlength=\"60\" /></div>\n";
$html .= " </div>\n";
$html .= " <div class=\"form-group\">\n";
$html .= "  <label class=\"control-label col-md-4\">Importer le certificat utilisateur (format PEM) :</label>\n";
$html .= "  <div class=\"col-md-6\">\n<input type=\"file\" name=\"certificate\" />\n</div>\n";
$html .= "</div>\n";
$html .= "  <div class=\"alert alert-info col-md-9 col-md-offset-1\">\n". 
get_hecho($him->get('subject_dn')) . "";
if ($him->get('certificate')){
    $html .= "</br>Expire le " .date("d/m/Y H:m:s",strtotime($x509Certificate->getExpirationDate($him->get('certificate'))));
}
$html .= "</div>\n";
$html .= " <div class=\"form-group\">\n";
$html .= "  <label class=\"control-label col-md-4\">État :</label>\n";
$html .= "  <div class=\"col-md-6 \">\n";
$status = ($val = Helpers::getFromSession("status")) ? $val : $him->get("status");

$html .= $doc->getHTMLSelect("status", $me->get("statusTypes"), $status);

$html .= "  </div>\n";
$html .= " </div>\n";

if ($me->isGroupAdminOrSuper()) {
  if ($me->isGroupAdmin()) {
	$cond = " WHERE authorities.authority_group_id=" . $me->get("authority_group_id")." ORDER BY authorities.name ASC";
  } else {
	$cond = " ORDER BY authorities.name ASC";
  }

  $authorities = Authority::getAuthoritiesIdName($cond);

  $html .= " <div class=\"form-group\">\n";
  $html .= "  <label class=\"control-label col-md-4\">Collectivité :</label>\n";
  $html .= "  <div class=\"col-md-6 \">\n";

  if (! $mod || $new_id) {
	$auth = ($val = Helpers::getFromSession("authority_id")) ? $val : $him->get("authority_id");

	$html .= $doc->getHTMLSelect("authority_id", $authorities, $auth);
  } else {
	$html .= $authorities[$him->get("authority_id")];
  }

  $html .= "  </div>\n";
  $html .= " </div>\n";
}

$html .= " <div class=\"form-group\">\n";
$html .= "  <label class=\"control-label col-md-4\">Rôle :</label>\n";
$html .= "  <div class=\"col-md-6 \">\n";

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

$html .= "  </div>\n";
$html .= " </div>\n";

if ($me->isSuper()) {
  $html .= " <div class=\"form-group\">\n";
  $html .= "  <label class=\"control-label col-md-4\">Groupe (pour un administrateur de groupe) :</label>\n";
  $html .= "  <div class=\"col-md-6 \">";

  $groups = Group::getGroupsIdName();

  $html .= $doc->getHTMLSelect("authority_group_id", $groups, $him->get("authority_group_id"));
  $html .= "</div>\n";
  $html .= " </div>\n";
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

foreach ($modules as $module) {
	if ($me->isSuper() || $authModules[$module["id"]]) {
	  $class = "";
	  if ($me->isSuper() && ! isset($authModules[$module["id"]])) {
		$class = " class=\"inactive\"";
	  }

		$html .= " <div class=\"form-group\">\n";
	  $html .= "    <label class=\"control-label col-md-4" . $class . "\">" . $module["description"] . " :</label>";
	  $html .= "    <div class=\"col-md-6  " . $class . "\">\n" . $doc->getHTMLSelect("perm_" . $module["id"], $me->getPermTypes($module['specific_perms']), $him->getPerm($module["name"])) . "</div>\n";
          $html .= " </div>\n";
	}
}



$html .= "<div class=\"form-group\">\n";
$html .= "<button type=\"submit\" class=\"col-md-offset-4 col-md-6 btn btn-default\">\n";
$html .= ($mod) ? "Valider les modifications" : "Ajouter l'utilisateur";
$html .= "</button>\n</div>\n";
$html .= "</form>\n";

// Note : UserSQL::getInfoFromCertificateInfo ne renvoie pas authority_name
$sql = "SELECT users.id, users.login, users.name, users.givenname, users.email, users.role, users.authority_group_id, users.telephone, users.status, users.authority_id, authorities.name AS authority_name";
$sql .= " FROM users LEFT OUTER JOIN authorities ON users.authority_id = authorities.id";
$sql .= " WHERE users.subject_dn = :subject_dn";
$sql .= " AND issuer_dn = :issuer_dn";
$sql .= " ORDER BY users.name";
$sql_params = array(
    'subject_dn' => $him->get("subject_dn"),
    'issuer_dn' => $him->get("issuer_dn")
);
$users_cert = $sqlQuery->query($sql, $sql_params);

$html .= "<h2>Autre rôle de l'utilisateur</h2>";
if (count($users_cert) > 1){
	$html .= "<div class=\"data_table\">\n";
	$html .= "<table class=\"data-table table table-striped\">";
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
	
	foreach($users_cert as $user_cert){
        $id_other = $user_cert['id'];
		if ($id_other == $him->getId()){
			continue;
		}
 		$html .= "<tr class=\"alternate" . ($i + 1) . "\">\n";
 		$html .= " <td>" . $user_cert["login"] . "</td>\n"; 		
  		$html .= " <td>" . $user_cert["givenname"] . " " . $user_cert["name"] . "</td>\n";
  		$html .= " <td><a href=\"mailto:" .$user_cert["email"]. "\">" . $user_cert["email"] . "</a></td>\n";
  		$html .= " <td>" . $rolesList[$user_cert["role"]] . "</td>\n";
  		$html .= " <td>" . $statusList[$user_cert["status"]] . "</td>\n";
  		$html .= " <td><a href=\"" . WEBSITE_SSL . "/admin/authorities/admin_authority_edit.php?id=" . $user_cert["authority_id"]. "\">" . $user_cert["authority_name"]  . "</a></td>\n";
  		$html .= " <td><a href=\"" . WEBSITE_SSL . "/admin/users/" . basename(__FILE__) . "?id=" .  $user_cert["id"] . "\" class=\"icon\"><img src=\"" . WEBSITE_SSL . "/custom/images/erreur.png\" alt=\"image_modif\" title=\"Modifier\" /></a></td>\n";
  		$html .= "</tr>\n";
  		 $i = ($i + 1) % 2;
	}
	$html .= '</table>';
}

if ($him->get('login')) {
	$html .="<a href='admin_user_edit.php?new_id=".($id?$id:$new_id)."'>Créer un nouveau rôle avec le même certificat </a>";
	if ($him->get('subject_dn')){
		$html .= "(".get_hecho($him->get('subject_dn')) .")";	
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

	$html .= "<form class=\"form form-horizontal\" action='add-user-to-service.php' method='post'>\n";
	$html .= "<input type='hidden' name='id_user' value='".$him->getId()."'>\n";
        $html .= "<div class=\"form-group\">\n";
	$html .= "<label class=\"col-md-3 control-label\"> Mettre dans le service : </label>\n<div class=\"col-md-3\">\n<select class=\"form-control\" name='id_service'>";
	foreach($services as $s){
		$html .= "<option value='".$s['id']."'>" . $s['name'] . "</option>";	
	}
	$html .= "</select>\n</div>\n";
	$html .= "<input class=\"btn btn-primary btn-sm col-md-2\" type='submit' value='Ajouter'>\n";
	$html .= "</div>\n";
	$html .= "</form>\n";
}

$html .= "</div>\n"; //Content...
$html .= "</div>\n"; //Content...
$html .= "</div>\n"; //Content...
$doc->addBody($html);

$doc->buildFooter();

$doc->display();
