<?php

$me = $this->me;

$fauthority = Helpers::getVarFromGet("authority");
$fmodule = Helpers::getVarFromGet("module");
$fuser = Helpers::getVarFromGet("user");
$fmessage = Helpers::getVarFromGet("message");
$fseverity = Helpers::getVarFromGet("severity");

$myAuthority = new Authority($me->get("authority_id"));

$log = new Log();

$doc = new HTMLLayout();

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


$html = $h1_title;

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

echo $html;