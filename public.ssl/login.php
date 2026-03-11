<?php

use S2lowLegacy\Class\HTMLLayout;
use S2lowLegacy\Class\HTMLLayoutFactory;
use S2lowLegacy\Class\S2lowRedirect;
use S2lowLegacy\Lib\X509Certificate;
use S2lowLegacy\Model\UserSQL;

/** @var HTMLLayoutFactory $htmlLayoutFactory */
list(    $s2lowRedirect ,$userSQL, $htmlLayoutFactory) = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray(
        [S2lowRedirect::class, UserSQL::class, HTMLLayoutFactory::class]
    );

$x509Certificate = new X509Certificate();
$certificateInfo = $x509Certificate->retrieveClientInfo();

$allUser = $userSQL->getInfoFromCertificateInfo($certificateInfo);

if (! $allUser) {
    $s2lowRedirect->redirect("/", "Certificat invalide");
}

$doc = $htmlLayoutFactory->createLayout();

$doc->setTitle(WEBSITE_TITLE);

$html = "<div id=\"content\" class=\"container\"><div class=\"row\">";
$html .= " <div class=\"col-md-12\" role=\"main\">\n";
$html .= " <h1>Connexion</h1>\n";
$html .= " </div>\n";

$html .= " <div class=\"col-md-12\">\n";
$html .= "<h2>Vous devez saisir votre identifiant et votre mot de passe</h2>";
$html .= " </div>\n";

$html .= " <div class=\"col-md-12\">\n";
$html .= "<form action=\"ident.php\" method=\"post\" name=\"form\" class=\"form-horizontal\" >";

$html .= "<div class=\"form-group\">";
$html .= "  <label for=\"login\" class=\"col-md-3 control-label\">Identifiant</label>";
$html .= "  <div class=\"col-md-3\"><input id=\"login\" class=\"form-control\" type=\"text\" name=\"login\" value=\"\" size=\"30\" maxlength=\"60\" /></div>\n";
$html .= " </div>\n";
$html .= "<div class=\"form-group\">";
$html .= "  <label for=\"password\" class=\"col-md-3 control-label\">Mot de passe</label>";
$html .= "  <div class=\"col-md-3\"><input id=\"password\" class=\"form-control\" type=\"password\" name=\"password\" value=\"\" size=\"30\" maxlength=\"60\" /></div>\n";
$html .= " </div>\n";

$html .= "<div class=\"form-group\" style=\"margin-top:40px;\">";
$html .= "<div class=\"col-md-3 col-md-offset-3\">";
$html .= "<input type=\"submit\" class=\"submit_button btn btn-primary\" value='Connexion' style=\"width:100%;\"/>";
$html .= " </div></div></form>\n";
$html .= " </div>\n";

$html .= "</div>\n";

$doc->addBody($html);

$doc->buildFooter();

$doc->display();
