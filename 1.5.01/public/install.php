<?php
require_once("../config/config.php");
require_once(SITEROOT . '/class/include.class.php');

// Vérification si l'application est déjà configurée ou pas
if (User::dbHasUser()) {
  $_SESSION["error"] = "L'application est déjà configurée.";
  header("Location: " . WEBSITE);
  exit();
}

$him = new User();

$doc = new HTMLLayout("xhtml_home_install.tpl.php");

$doc->addHeader("<script src=\"" . WEBSITE . "/javascript/validateform.js\" type=\"text/javascript\"></script>\n");

$doc->setTitle("Tedetis : initialisation de l'application");

$html = "<div id=\"content\">\n";
$html .= "<h1>Création de l'administrateur initial de l'application</h1>\n";
$html .= "<form action=\"" . WEBSITE . "/install_handler.php\" method=\"post\" name=\"form\" enctype=\"multipart/form-data\" onsubmit=\"javascript:return validateForm(" . $him->getValidationTrio('name', 'givenname', 'email') . ", 'certificate', 'Certificat utilisateur', 'RisString');\">\n";
$html .= "<div class=\"data_table\">\n";
$html .= "<table class=\"data\">\n";
$html .= " <tr>\n";
$html .= "  <td class=\"td-register\">Nom&nbsp;:</td>\n";
$html .= "  <td class=\"td-input\"><input type=\"text\" name=\"name\"  size=\"30\" maxlength=\"60\" /></td>\n";
$html .= " </tr>\n";
$html .= " <tr>\n";
$html .= "  <td class=\"td-register\">Pr&eacute;nom&nbsp;:</td>\n";
$html .= "  <td class=\"td-input\"><input type=\"text\" name=\"givenname\" size=\"30\" maxlength=\"60\" /></td>\n";
$html .= " </tr>\n";
$html .= " <tr>\n";
$html .= "  <td class=\"td-register\">Adresse électronique&nbsp;:</td>\n";
$html .= "  <td class=\"td-input\"><input type=\"text\" name=\"email\" size=\"30\" maxlength=\"60\" /></td>\n";
$html .= " </tr>\n";
$html .= " <tr>\n";
$html .= "  <td class=\"td-register\">Téléphone&nbsp;:</td>\n";
$html .= "  <td class=\"td-input\"><input type=\"text\" name=\"telephone\" size=\"30\" maxlength=\"60\" /></td>\n";
$html .= " </tr>\n";
$html .= " <tr>\n";
$html .= "  <td class=\"td-register\">Importer le certificat utilisateur (format PEM)&nbsp;:</td>\n";
$html .= "  <td class=\"td-input\"><input type=\"file\" name=\"certificate\" /></td>\n";
$html .= " </tr>\n";
$html .= "</table>\n";
$html .= "</div>\n";
$html .= "<center><input type=\"submit\" class=\"submit_button\" value=\"Ajouter l'utilisateur\" /></center>\n";
$html .= "</form>\n";
$html .= "</div>\n";

$doc->addBody($html);

$doc->display();
?>
