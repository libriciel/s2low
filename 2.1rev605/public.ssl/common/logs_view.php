<?php
/*
 * TéDéTIS - Copyright 2006 Alternance-Soft
 * Contributeur : Jérôme Schell, Août 2006 
 *
 * contact@alternancesoft.com
 *
 * Ce logiciel est un programme informatique servant à la
 * dématérialisation de l'administration. 
 *
 * Ce logiciel est régi par la licence CeCILL soumise au droit français et
 * respectant les principes de diffusion des logiciels libres. Vous pouvez
 * utiliser, modifier et/ou redistribuer ce programme sous les conditions
 * de la licence CeCILL telle que diffusée par le CEA, le CNRS et l'INRIA 
 * sur le site "http://www.cecill.info".
 *
 * En contrepartie de l'accessibilité au code source et des droits de copie,
 * de modification et de redistribution accordés par cette licence, il n'est
 * offert aux utilisateurs qu'une garantie limitée.  Pour les mêmes raisons,
 * seule une responsabilité restreinte pèse sur l'auteur du programme,  le
 * titulaire des droits patrimoniaux et les concédants successifs.
 *
 * A cet égard  l'attention de l'utilisateur est attirée sur les risques
 * associés au chargement,  à l'utilisation,  à la modification et/ou au
 * développement et à la reproduction du logiciel par l'utilisateur étant 
 * donné sa spécificité de logiciel libre, qui peut le rendre complexe à 
 * manipuler et qui le réserve donc à des développeurs et des professionnels
 * avertis possédant  des  connaissances  informatiques approfondies.  Les
 * utilisateurs sont donc invités à charger  et  tester  l'adéquation  du
 * logiciel à leurs besoins dans des conditions permettant d'assurer la
 * sécurité de leurs systèmes et ou de leurs données et, plus généralement, 
 * à l'utiliser et l'exploiter dans les mêmes conditions de sécurité. 
 *
 * Le fait que vous puissiez accéder à cet en-tête signifie que vous avez 
 * pris connaissance de la licence CeCILL, et que vous en avez accepté les
 * termes.
*/
?>
<?php
/**
 * \file logs_view.php
 * \brief Page d'affichage du journal d'évènements
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 18.08.2006
 * 
 *
 * Cette page affiche les entrées du journal concernant l'utilisateur 
 * connectïé.
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

// Configuration
require_once("../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');

$me = new User();

if (! $me->authenticate()) {
  $_SESSION["error"] = "Echec de l'authentification";
  header("Location: " . WEBSITE);
  exit();
}

$fauthority = Helpers::getVarFromGet("authority");
$fmodule = Helpers::getVarFromGet("module");
$fuser = Helpers::getVarFromGet("user");
$fmessage = Helpers::getVarFromGet("message");
$fseverity = Helpers::getVarFromGet("severity");

$myAuthority = new Authority($me->get("authority_id"));

$log = new Log();

$doc = new HTMLLayout();

$doc->setTitle("Tedetis : Journal d'évènements");

$doc->openContainer();
$doc->openSideBar();
$doc->buildMenu($me);

$filter = array();
// Construction chaîne de filtrage
if ($me->isAdmin()) {
  if (isset($fuser) && strlen($fuser) > 0) {
	$filter[] .= "(users.name ILIKE '%" . addslashes($fuser) . "%' OR users.givenname ILIKE '%" . addslashes($fuser) . "%')";
  }

  if ($me->isGroupAdmin()) {
	$filter[] .= "(authorities.authority_group_id='" . addslashes($me->get("authority_group_id")) . "' OR logs.user_id=" . $me->getId() . ")";
  }

  if ($me->isGroupAdminOrSuper()) {
	if (isset($fauthority) && strlen($fauthority) > 0) {
	  $filter[] .= "users.authority_id='" . addslashes($fauthority) . "'";
	}
  } elseif ($me->isAuthorityAdmin()) { // Un admin d'une collectivité ne voit forcément que les entrées concernant sa collectivité
	$filter[] .= "users.authority_id='" . $me->get("authority_id") . "'";
  }
} else {
  $filter[] .= "logs.user_id='" . $me->getId() . "'";
}

if (isset($fmodule) && strlen($fmodule) > 0) {
  $filter[] .= "logs.module='" . addslashes($fmodule) . "'";
}

if (isset($fseverity) && is_numeric($fseverity)) {
  $filter[] .= "logs.severity='" . addslashes($fseverity) . "'";
}

if (isset($fmessage) && strlen($fmessage) > 0) {
  $filter[] .= "logs.message ILIKE '%" . addslashes($fmessage) . "%'";
}

if (! $me->isSuper()) {
  $filter[] = "logs.visibility != 'SADM'";
}

if (! $me->isAdmin()) {
  $filter[] = "logs.visibility != 'ADM'";
}

$where = "";
if (count($filter) > 0) {
  $where = "WHERE " . implode($filter, " AND ");
}

$logEntries = $log->getLogEntriesList($where);
$severities = $log->get("severities");

$doc->buildPager($log);
$doc->closeSideBar();
$doc->openContent();

$html = "<h1>Journal d'évènements";

if ($me->isAuthorityAdmin()) {
  $html .= " de la collectivité «&nbsp;" . $myAuthority->get("name") . "&nbsp;»";
} elseif ($me->isGroupAdmin()) {
  $myGroup = new Group($me->get("authority_group_id"));
  $html .= " du groupe «&nbsp;" . $myGroup->get("name") . "&nbsp;»";
}

$html .= "</h1>\n";
$html .= "<div id=\"filtering_area\">\n";
$html .= "<h2>Filtrage</h2>\n";
$html .= "<form class=\"form-horizontal\" action=\"logs_view.php\" method=\"get\" role=\"form\">\n";
$html .= "<div class=\"form-group\">\n";
$html .= "<label for=\"module\" class=\"col-md-3 control-label\">Module</label>\n";
$html .= "<div class=\"col-md-3\">" . $doc->getHTMLSelect("module", Module::getActiveModulesNames(), $fmodule) . "</div>\n";
$html .= "<label for=\"severity-choice\" class=\"col-md-3 control-label\">Sévérité</label>\n";
$html .= "<div class=\"col-md-3\">" . $doc->getHTMLSelect("severity", $log->get("severities"), $fseverity) . "</div>\n";
$html .= "</div>\n";
$html .= "<div class=\"form-group\">\n";
$html .= "<label for=\"msg-contain\" class=\"col-md-3 control-label\">Message contient</label>\n";
$html .= "<div class=\"col-md-3\"><input id=\"msg-contain\" class=\"form-control\" type=\"text\" name=\"message\" size=\"20\" maxlength=\"25\"";

if (strlen($fmessage) > 0) {
  $html .= " value=\"" . get_hecho($fmessage) . "\"";
}

$html .= " /></div>\n";

if ($me->isAdmin()) {
  $html .= "<label for=\"username-contain\" class=\"col-md-3 control-label\">Nom utilisateur contient</label>\n";
  $html .= "<div class=\"col-md-3\"><input id=\"username-contain\" class=\"form-control\" type=\"text\" name=\"user\" size=\"20\" maxlength=\"25\"";

  if (strlen($fuser) > 0) {
      $html .= " value=\"" . get_hecho($fmessage) . "\"";
  }

  $html .= " /></div>\n</div>\n";

  if ($me->isGroupAdminOrSuper()) {
	if ($me->isGroupAdmin()) {
	  $cond = " WHERE authorities.authority_group_id=" . $me->get("authority_group_id")." ORDER BY authorities.name ASC";
	} else {
	  $cond = " ORDER BY authorities.name ASC";
	}
        $html .= "<div class=\"form-group\">\n";
        $html .= "<label for=\"collectivity-choice\" class=\"col-md-3 control-label\">Collectivité</label>\n";
        $html .= "<div class=\"col-md-3\">". $doc->getHTMLSelect("authority", Authority::getAuthoritiesIdName($cond), $fauthority) . "</div>\n";
        $html .= "</div>\n";
  }
} else {
  $html .= "</div>\n";
}
$html .= "<div class=\"form-group\">";
$html .= "<button type=\"submit\" class=\"col-md-offset-3 col-md-3 btn btn-default\">Filtrer</button>\n";
$html .= "</div>\n";
$html .= "</form>\n";
$html .= "</div>\n";


$html .= "<h2>Entrées du journal</h2>\n";
$html .= "<div id=\"journal_area\">\n";
if (count($logEntries) > 0) {
  $html .= "<table class=\"logs data-table table table-striped\" summary=\"Ce tableau présente respectivement la date, l'auteur, la sévérité, le module, le message et un lien vers une archive de chaque événement du journal\">";
  $html .= "<caption>Liste des événements du journal en fonction des choix de filtrage</ acption>";
  $html .= "<thead>\n";
  $html .= "<tr>\n";
  $html .= " <th id=\"date\" class=\"data\">Date</th>\n";
  $html .= " <th id=\"author\" class=\"data\">Créé par</th>\n";
  $html .= " <th id=\"severity\" class=\"data\">Sévérité</th>\n";
  $html .= " <th id=\"module\" class=\"data\">Module</th>\n";
  $html .= " <th id=\"user\" class=\"data\">Utilisateur</th>\n";
  $html .= " <th id=\"message\" class=\"data\">Message</th>\n";
  $html .= " <th id=\"timestamp\" class=\"data\">Horodatage</th>\n";
  $html .= "</tr>\n";
  $html .= "</thead>\n";
  $html .= "<tbody>\n";

  $i = 0;

  foreach ($logEntries as $logEntry) {
	$owner = null;
	if (isset($logEntry["user_id"])) {
	  $owner = new User($logEntry["user_id"]);
	  if (! $owner->init()) {
		$owner = null;
	  }
	}

	$html .= "<tr>\n";
	$html .= " <td headers=\"date\">" . Helpers::getDateFromBDDDate($logEntry["date"], true) . "</td>\n";
	$html .= " <td headers=\"author\">" . get_hecho($logEntry["issuer"]) . "</td>\n";
	$html .= " <td headers=\"severity\">" . get_hecho($severities[$logEntry["severity"]]) . "</td>\n";
	$html .= " <td headers=\"module\">" . get_hecho($logEntry["module"]) . "</td>\n";
	$html .= " <td headers=\"user\">" . (($owner) ? get_hecho($owner->getPrettyName()) : "") . "</td>\n";
	$html .= " <td class=\"long_field\" headers=\"message\">" . nl2br(get_hecho($logEntry["message"])) . "</td>\n";
	$html .= " <td headers=\"timestamp\"><a href=\"" . WEBSITE_SSL . "/common/logs_get_timestamp.php?id=" . $logEntry["id"] . "\" title=\"Télécharger une archive contenant l'entrée de journal n°" .$logEntry["id"] . " et sa signature\" class=\"icon\"><img src=\"" . WEBSITE_SSL . "/custom/images/timestamping_icon.png\" alt=\"timestamp\" /></a></td>\n";
	$html .= "</tr>\n";
  }

  $html .= "</tbody>\n";
  $html .= "</table>\n";
} else {
  $html .= "Pas d'entrée de journal correspondant au filtrage spécifié.";
}



$doc->addBody($html);


$doc->closeContent();
$doc->closeContainer();

$doc->buildFooter();

$doc->display();

?>
