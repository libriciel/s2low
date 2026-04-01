<?php

use S2lowLegacy\Class\HTMLLayout;

// Cette page affiche uniquement le formulaire de login
// L'authentification est gérée par Symfony Security

$doc = new HTMLLayout();

$doc->setTitle(WEBSITE_TITLE);

$html = "<div id=\"content\" class=\"container\"><div class=\"row\">";
$html .= " <div class=\"col-md-12\" role=\"main\">\n";
$html .= " <h1>Connexion</h1>\n";
$html .= " </div>\n";

// Afficher les erreurs éventuelles
$error = $_GET['error'] ?? null;
if ($error) {
    $errorMessages = [
        'multiple_accounts' => ['message' => 'Plusieurs comptes sont associés à ce certificat. Veuillez vous identifier.', 'type-alert' => 'info'],
        'bad_credentials' => ['message' => 'Les informations saisies sont incorrect.', 'type-alert' => 'danger'],
        'empty_login' => ['message' => 'Identifiant manquant.', 'type-alert' => 'danger'],
        'empty_password' => ['message' => 'Mot de passe manquant.', 'type-alert' => 'danger'],
    ];
    $errorMessage = $errorMessages[$error]['message'] ?? 'Erreur d\'authentification.';
    $errorTypeAlert = $errorMessages[$error]['type-alert'] ?? 'danger';
    $html .= " <div class=\"col-md-12\">\n";
    $html .= "<div class=\"alert alert-" . $errorTypeAlert . "\">" . htmlspecialchars($errorMessage) . "</div>";
    $html .= " </div>\n";
}

$html .= " <div class=\"col-md-12\">\n";
$html .= "<h2>Vous devez saisir votre identifiant et votre mot de passe</h2>";
$html .= " </div>\n";

$html .= " <div class=\"col-md-12\">\n";
$html .= "<form action=\"" . htmlspecialchars('login.php') . "\" method=\"post\" name=\"form\" class=\"form-horizontal\" >";

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
