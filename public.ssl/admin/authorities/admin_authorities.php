<?php

require_once( __DIR__ . "/../../../init/init.php");

$frontController->go("Admin","authorities");
/*
$me = new User();

if (! $me->authenticate()) {
  $_SESSION["error"] = "Échec de l'authentification";
  header("Location: " . WEBSITE);
  exit();
}

if (! $me->isGroupAdminOrSuper()) {
  $_SESSION["error"] = "Accès refusé";
  header("Location: " . WEBSITE_SSL);
  exit();
}

$ftype =  Helpers::getVarFromGet("type");
$fname = Helpers::getVarFromGet("name");
$fgroup = Helpers::getVarFromGet("group");
$api = Helpers::getVarFromGet("api");
$fsiren = Helpers::getVarFromGet("siren");
$count = Helpers::getVarFromGet("count")?:10;

$recuperateur = new Recuperateur($_GET);

$page_number = $recuperateur->getInt('page',1);
$taille_page =  $recuperateur->getInt('count',10);

$authoritySQL = new AuthoritySQL($sqlQuery);
$authorities = $authoritySQL->getList($fgroup, $ftype,$fname,$fsiren,($page_number - 1) * $taille_page,$taille_page);

$nb_authorities = $authoritySQL->getNb($fgroup, $ftype,$fname,$fsiren);

if ($api){
	$jsonOutput->retrictAndDisplay($authorities,array('id','name','authority_group_id','siren','address','city','postal_code','telephone'));
	exit;
}


$doc = new HTMLLayout();
$doc->setTitle("Tedetis : gestion des collectivités");
$doc->openContainer();
$doc->openSideBar();
$doc->buildMenu($me);

$pagerHTML  = new PagerHTML();

$doc->addBody($pagerHTML->getHTML($page_number,$nb_authorities,$taille_page));
$doc->closeSideBar();
$doc->openContent();

$html = "<h1>Gestion des collectivités";

if ($me->isGroupAdmin()) {
  $myGroup = new Group($me->get("authority_group_id"));
  $html .= " du groupe " . get_hecho($myGroup->get("name"));
}

$html .= "</h1>\n";
$html .= "<div id=\"actions-area\">\n";
$html .= "<h2>Actions</h2>";
$html .= "<a class=\"btn btn-primary\" href=\"" . WEBSITE_SSL . "/admin/authorities/admin_authority_edit.php\" class=\"bouton\">Ajouter une collectivité</a>\n";
$html .= "</div>\n";
$html .= "<div id=\"filtering-area\">\n";
$html .= "<h2>Filtrage</h2>\n";
$html .= "<form class=\"form-horizontal\" action=\"admin_authorities.php\" method=\"get\" role=\"form\">\n";
$html .= "<div class=\"form-group\">\n";
$html .= "<label for=\"type\" class=\"col-md-3 control-label\">Type</label>\n";
$html .= "<div class=\"col-md-3\">" . $doc->getHTMLSelect("type", Authority::getAuthorityTypesIdName(), $ftype) . "</div>\n";
$html .= "<label for=\"name-contain\" class=\"col-md-3 control-label\">Le nom contient</label>\n";
$html .= "<div class=\"col-md-3\"><input id=\"name-contain\" class=\"form-control\" type=\"text\" name=\"name\" size=\"20\" maxlength=\"25\"";

if (strlen($fname) > 0) {
  $html .= " value=\"" . get_hecho($fname) . "\"";
}

$html .= " /></div>\n";
$html .= "</div>\n";

if ($me->isSuper()) {
  $html .= "<div class=\"form-group\">\n";
  $html .= "<label for=\"group\" class=\"col-md-3 control-label\">Groupe</label>\n";
  $html .= "<div class=\"col-md-3\">" . $doc->getHTMLSelect("group", Group::getGroupsIdName(), $fgroup) . "</div>\n";
  $html .= "</div>\n";
}
$html .= "<div class=\"form-group\">\n";
$html .= "<button class=\"btn btn-default col-md-offset-3 col-md-3\" type=\"submit\">Filtrer</button>\n";
$html .= "</div>\n";
$html .= "</form>\n";
$html .= "</div><br />\n";
$html .= "<h2>Liste des collectivités</h2>\n";
$html .= "<div id=\"authority-list\">\n";
$html .= "<table class=\"data-table table table-striped\" summary=\"\">";
$html .= "<thead>\n";
$html .= "<tr>\n";
$html .= " <th id=\"name\">Nom</th>\n";
$html .= " <th id=\"group-member\">Groupe</th>\n";
$html .= " <th id=\"authority-type\">Type de collectivité</th>\n";
$html .= " <th id=\"address\">Adresse</th>\n";
$html .= " <th id=\"phone\">Téléphone</th>\n";
$html .= " <th id=\"fax\">Fax</th>\n";
$html .= " <th id=\"actions\">Actions</th>\n";
$html .= "</tr>\n";
$html .= "</thead>\n";
$html .= "<tbody>\n";

$i = 0;

foreach ($authorities as $ent) {
  $group = new Group($ent["authority_group_id"]);

  if (strlen($group->get("name")) > 0) {
	$groupName = $group->get("name");
  } else {
	$groupName = "Aucun";
  }

  $html .= "<tr>\n";
  $html .= " <td headers=\"name\">" . $ent["name"] . "</td>\n";
  $html .= " <td headers=\"group-member\">" . $groupName . "</td>\n";
  $html .= " <td headers=\"authority-type\">" . $ent["type_name"] . "</td>\n";
  $html .= " <td headers=\"address\" class=\"long_field\">" . nl2br($ent["address"]) . "<br />" . $ent["postal_code"] . " " . $ent["city"] . "</td>\n";
  $html .= " <td headers=\"phone\">" . $ent["telephone"] . "</td>\n";
  $html .= " <td headers=\"fax\">" . $ent["fax"] . "</td>\n";
  $html .= " <td headers=\"actions\"><a href=\"admin_authority_edit.php?id=" . $ent["id"] . "\" class=\"icon\"><img src=\"" . WEBSITE_SSL . "/custom/images/erreur.png\" alt=\"image_modif\" title=\"Modifier\" /></a></td>\n";
  $html .= "</tr>\n";

}

$html .= "</tbody>\n";
$html .= "</table>\n";
$html .= "</div>\n";
$html .= "</div>\n";

$doc->addBody($html);

$doc->closeContent();
$doc->closeContainer();

$doc->buildFooter();

$doc->display();
*/