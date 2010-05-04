<?php 

// Configuration
require_once("../config/config.php");
require_once(SITEROOT . '/class/include.class.php');


$doc = new HTMLLayout();

$doc->setTitle(WEBSITE_TITLE);

//$doc->buildMenu($me);

$html = "<div id=\"content\">";
$html .= " <h1>Connexion</h1>\n";

$html .= "<br />\n";


$html .= "<h2>Vous devez saisir votre identifiant et votre mot de passe</h2>";

$html .= "<form action=\"ident.php\" method=\"post\" name=\"form\"  >";

$html .= "<div class=\"data_table\">\n";
$html .= "<table class=\"data\">\n";
$html .= " <tr>\n";
$html .= "  <td class=\"td-register\">Identifiant&nbsp;:</td>\n";
$html .= "  <td class=\"td-input\"><input type=\"text\" name=\"login\" value=\"\" size=\"30\" maxlength=\"60\" /></td>\n";
$html .= " </tr>\n";
$html .= " <tr>\n";
$html .= "  <td class=\"td-register\">Mot de passe&nbsp;:</td>\n";
$html .= "  <td class=\"td-input\"><input type=\"password\" name=\"password\" value=\"\" size=\"30\" maxlength=\"60\" /></td>\n";
$html .= " </tr>\n";
$html .= " </table>";

$html .= "<center><input type=\"submit\" class=\"submit_button\" value='Connexion' /></center></form>";

$html .= "<br />\n";

$html .= "</p>\n";
$html .= "</div>\n";

$doc->addBody($html);

$doc->buildFooter();

$doc->display();
//test
?>
