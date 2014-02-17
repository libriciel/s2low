<?php
require_once ("../../../config/config.php");
require_once (SITEROOT . '/class/include.class.php');

$module = new Module();
if (!$module->initByName("helios")) {
  $_SESSION["error"] = "Erreur d'initialisation du module";
  header("Location: " . WEBSITE_SSL);
  exit ();
}

$me = new User();

if (!$me->authenticate()) {
  $_SESSION["error"] = "Échec de l'authentification";
  header("Location: " . WEBSITE);
  exit ();
}

if ($me->isGroupAdminOrSuper() || !$module->isActive() || !$me->checkDroit($module->get("name"),'CS')) {
  $_SESSION["error"] = "Accès refusée";
  header("Location: " . WEBSITE_SSL);
  exit ();
}

if ($module->getParam("paper") == "on") {
  $_SESSION["error"] = "Mode &nbsp;papier&nbsp; actif. Accès interdit.";
  header("Location: " . WEBSITE_SSL . "/modules/helios/");
  exit ();
}

$myAuthority = new Authority($me->get("authority_id"));

$doc = new HTMLLayout();

$js =<<<EOJS
<script type="text/javascript">
//<![CDATA[

var progress_bar = new Image();
progress_bar.src = "/custom/images/progress_bar.gif";

//]]>
</script>
EOJS;

$doc->addHeader($js);

$doc->addHeader("<script src=\"/javascript/validateform.js\" type=\"text/javascript\"></script>\n");

$doc->setTitle("Tedetis : Helios - Import d'une enveloppe");

$doc->buildMenu($me);

$html .= "<div id=\"content\">\n";
$html .= "<h1>Helios - Dématérialisation de documents comptables</h1>\n";
$html .= "<center><a href=\"" . WEBSITE_SSL . "/modules/helios/\" class=\"bouton\">Retour liste transactions</a></center>\n";
$html .= "<h2>Import d'un fichier</h2>\n";
$html .= "<form method=\"POST\" enctype=\"multipart/form-data\" ";
$html .= " action=\"" . WEBSITE_SSL . "/modules/helios/helios_script_reception.php\" > ";
$html .= "<table  style='text-align:right''>";
$html .= "<tr><td>Fichier XML : </td><td><input type=\"FILE\" name=\"enveloppe\"/></td></tr>";
$html .= "<tr><td>Signer le fichier PES avant de le télétransmettre : </td><td style='text-align:left'><input type=\"checkbox\"  name=\"must_signed\" /></td></tr>\n";
$html .= "<tr><td colspan='2' style='text-align:center'><input class=\"submit_button\" type=\"submit\" value=\" Importer un fichier\" ></td></tr>";

$html .= "</table>";
$html .= "</form>";

$html .= "</div>\n";

$doc->addBody($html);
$doc->buildFooter();
$doc->display();

