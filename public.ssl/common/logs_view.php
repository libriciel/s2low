<?php
/*
 * TéDéTIS - Copyright 2006 Alternance-Soft
 * Contributeur : Jérôme Schell, Août 2006 
 *
 * contact@alternancesoft.com
 *
 * Ce logiciel est un programme informatique servant à la
 * dématèrialisation de l'administration. 
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

$doc->setTitle("Tedetis : Journal d'ï¿½vï¿½nements");

$doc->buildMenu($me);

$html = "<div id=\"content\">\n";
$html .= "<h1>Journal d'évènements";

if ($me->isAuthorityAdmin()) {
  $html .= " de la collectivité «&nbsp;" . $myAuthority->get("name") . "&nbsp;»";
} elseif ($me->isGroupAdmin()) {
  $myGroup = new Group($me->get("authority_group_id"));
  $html .= " du groupe «&nbsp;" . $myGroup->get("name") . "&nbsp;»";
}

$html .= "</h1>\n";
$html .= "<div id=\"filtering_area\">\n";
$html .= "<h2>Filtrage</h2>\n";
$html .= "<form action=\"logs_view.php\" method=\"get\">\n";
$html .= "<table>\n";
$html .= "<tr>\n";
$html .= "<td class=\"title\">Module&nbsp;:</td>\n";
$html .= "<td class=\"value\">" . $doc->getHTMLSelect("module", Module::getActiveModulesNames(), $fmodule) . "</td>\n";
$html .= "<td class=\"title\">Sévérité&nbsp;:</td>\n";
$html .= "<td class=\"value\">" . $doc->getHTMLSelect("severity", $log->get("severities"), $fseverity) . "</td>\n";
$html .= "</tr>\n";
$html .= "<tr>\n";
$html .= "<td class=\"title\">Message contient&nbsp;:</td>\n";
$html .= "<td class=\"value\"><input type=\"text\" name=\"message\" size=\"20\" maxlength=\"25\"";

if (strlen($fmessage) > 0) {
  $html .= " value=\"" . htmlspecialchars($fmessage) . "\"";
}

$html .= " /></td>\n";

if ($me->isAdmin()) {
  $html .= "<td class=\"title\">Nom utilisateur contient&nbsp;:</td>\n";
  $html .= "<td class=\"value\"><input type=\"text\" name=\"user\" size=\"20\" maxlength=\"25\"";

  if (strlen($fuser) > 0) {
	$html .= " value=\"" . htmlspecialchars($fuser) . "\"";
  }

  $html .= " /></td>\n";
  $html .= "</tr>\n";
  $html .= "<tr>\n";
  $colspan = 4;

  if ($me->isGroupAdminOrSuper()) {
	if ($me->isGroupAdmin()) {
	  $cond = " WHERE authorities.authority_group_id=" . $me->get("authority_group_id")." ORDER BY authorities.name ASC";
	} else {
	  $cond = " ORDER BY authorities.name ASC";
	}

	$html .= "<td class=\"title\">Collectivité&nbsp;:</td>\n";
	$html .= "<td class=\"value\">" . $doc->getHTMLSelect("authority", Authority::getAuthoritiesIdName($cond), $fauthority) . "</td>\n";
	$colspan = 2;
  }

  $html .= "<td colspan=\"" . $colspan . "\"><input class=\"submit_button\" type=\"submit\" value=\"Filtrer\" /></td>\n";
} else {
  $html .= "<td><input class=\"submit_button\" type=\"submit\" value=\"Filtrer\" /></td>\n";
}
$html .= "</tr>\n";
$html .= "</table>\n";
$html .= "</form>\n";
$html .= "</div><br />\n";

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


$html .= "<h2>Entrées du journal</h2>\n";

if (count($logEntries) > 0) {
  $html .= "<table cellpadding=\"3\" cellspacing=\"2\" class=\"logs\">";
  $html .= "<tr>\n";
  $html .= " <th class=\"data\">Date</th>\n";
  $html .= " <th class=\"data\">Créé par</th>\n";
  $html .= " <th class=\"data\">Sévérité</th>\n";
  $html .= " <th class=\"data\">Module</th>\n";
  $html .= " <th class=\"data\">Utilisateur</th>\n";
  $html .= " <th class=\"data\">Message</th>\n";
  $html .= " <th class=\"data\">Horodatage</th>\n";
  $html .= "</tr>\n";

  $i = 0;

  foreach ($logEntries as $logEntry) {
	$owner = null;
	if (isset($logEntry["user_id"])) {
	  $owner = new User($logEntry["user_id"]);
	  if (! $owner->init()) {
		$owner = null;
	  }
	}

	$html .= "<tr class=\"alternate" . ($i + 1) . "\">\n";
	$html .= " <td>" . Helpers::getDateFromBDDDate($logEntry["date"], true) . "</td>\n";
	$html .= " <td>" . htmlspecialchars($logEntry["issuer"]) . "</td>\n";
	$html .= " <td>" . htmlspecialchars($severities[$logEntry["severity"]]) . "</td>\n";
	$html .= " <td>" . htmlspecialchars($logEntry["module"]) . "</td>\n";
	$html .= " <td>" . (($owner) ? htmlspecialchars($owner->getPrettyName()) : "") . "</td>\n";
	$html .= " <td class=\"long_field\">" . nl2br(htmlspecialchars($logEntry["message"])) . "</td>\n";
	$html .= " <td><a href=\"" . WEBSITE_SSL . "/common/logs_get_timestamp.php?id=" . $logEntry["id"] . "\" title=\"Télécharger une archive contenant l'entrée de journal n°" .$logEntry["id"] . " et sa signature\" class=\"icon\"><img src=\"" . WEBSITE_SSL . "/custom/images/timestamping_icon.png\" alt=\"timestamp\" /></a></td>\n";
	$html .= "</tr>\n";

	$i = ($i + 1) % 2;
  }

  $html .= "</table>\n";
} else {
  $html .= "Pas d'entrée de journal correspondant au filtrage spécifié.";
}


$html .= "</div>\n";

$doc->buildPager($log);

$doc->addBody($html);

$doc->buildFooter();

$doc->display();

?>
