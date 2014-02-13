<?php

// Configuration
require_once("../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');

// Instanciation du module courant
$module = new Module();
if (! $module->initByName("actes")) {
  $_SESSION["error"] = "Erreur d'initialisation du module";
  header("Location: " . WEBSITE_SSL);
  exit();
}

$me = new User();

if (! $me->authenticate()) {
  $_SESSION["error"] = "Échec de l'authentification";
  header("Location: " . WEBSITE);
  exit();
}

if ($me->isGroupAdminOrSuper() || ! $module->isActive()||!$me->checkDroit($module->get("name"),'CS')) {
  $_SESSION["error"] = "Accès refusé";
  header("Location: " . WEBSITE_SSL);
  exit();
}

if ($module->getParam("paper") == "on") {
  $_SESSION["error"] = "Mode «&nbsp;papier&nbsp;» actif. Accès interdit.";
  header("Location: " . WEBSITE_SSL . "/modules/actes/");
  exit();
}

$myAuthority = new Authority($me->get("authority_id"));

$doc = new HTMLLayout();

$js = <<<EOJS
<script type="text/javascript">
//<![CDATA[

var progress_bar = new Image();
progress_bar.src = "/custom/images/progress_bar.gif";

//]]>
</script>
EOJS;

$doc->addHeader($js);

$doc->addHeader("<script src=\"/javascript/validateform.js\" type=\"text/javascript\"></script>\n");

$doc->setTitle("Tedetis : Actes - Import d'une enveloppe");

$doc->buildMenu($me);

// Zone contenu
$html .= "<div id=\"content\">\n";
$html .= "<h1>ACTES - Dématérialisation du contrôle de légalité</h1>\n";
$html .= "<p style='text-align:center'><a href=\"" . WEBSITE_SSL . "/modules/actes/\" class=\"bouton\">Retour liste transactions</a></p>\n";
$html .= "<h2>Import d'une enveloppe</h2>\n";
$html .= "<form action=\"" . WEBSITE_SSL . "/modules/actes/actes_transac_submit.php\" method=\"post\" enctype=\"multipart/form-data\" onsubmit=\"javascript:if (validateForm('enveloppe', 'Fichier enveloppe', 'RisString')) { toggle_upload('form_progress', progress_bar); return true; } else { return false; }\">\n";
$html .= "<p>Indiquez le fichier archive de l'enveloppe à importer (taille maximum 20Mo)&nbsp;:<br />\n";
$html .= "<input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"20971520\" />\n";
$html .= "<input type=\"file\" id=\"enveloppe\" name=\"enveloppe\" size=\"40\" maxlength=\"255\" /><br /></p>\n";
$html .= "<div id=\"form_progress\"><input class=\"submit_button\" type=\"submit\" value=\"Importer l'enveloppe\" /></div>\n";
$html .= "</form>\n";
$html .= "</div>\n";

$doc->addBody($html);

$doc->buildFooter();

$doc->display();
