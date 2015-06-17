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

$doc->openContainer();
$doc->openSideBar();
$doc->buildMenu($me);
$doc->closeSideBar();
$doc->openContent();

$html = "<h1>Helios - Dématérialisation de documents comptables</h1>\n";
$html .= "<p id=\"back-transaction-btn\"><a class=\"btn btn-default\" href=\"" . WEBSITE_SSL . "/modules/helios/\" class=\"bouton\">Retour liste transactions</a></p>\n";
$html .= "<h2>Import d'un fichier</h2>\n";
$html .= "<form class=\"form-horizontal import-file-form\" method=\"POST\" enctype=\"multipart/form-data\" ";
$html .= " action=\"" . WEBSITE_SSL . "/modules/helios/helios_script_reception.php\" > ";

$html .= "<div class=\"form-group\">";
$html .= "<label for=\"enveloppe\" class=\"col-md-2 control-label\">Fichier XML</label>\n";
$html .= "<div class=\"col-md-6\">";
$html .= "<input type=\"file\" id=\"enveloppe\" name=\"enveloppe\"/>";
$html .= "</div>\n";
$html .= "</div>\n";


$html .= "<div class=\"form-group\">";
$html .= "<label for=\"must_signed\" class=\"col-md-2 control-label\">Signer le fichier PES avant de le télétransmettre</label>\n";
$html .= "<div class=\"col-md-6\">";
$html .= "<input type=\"checkbox\"  name=\"must_signed\" />";
$html .= "</div>\n";
$html .= "</div>\n";



$html .= "<div class=\"form-group\"><div class=\"col-md-2\"><button class=\"btn btn-primary\" type=\"submit\" value=\"\" />Importer le fichier</button></div></div>\n";
$html .= "</form>\n";


$html .= "</table>";
$html .= "</form>";

$html .= "</div>\n";

$doc->addBody($html);
$doc->closeContent();
$doc->closeContainer();

$doc->buildFooter();
$doc->display();

