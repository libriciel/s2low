<?php
/*
 * TéDéTIS - Copyright 2006 Alternance-Soft
 * Contributeur : Jérôme Schell, AoÃ»t 2006 
 *
 * contact@alternancesoft.com
 *
 * Ce logiciel est un programme informatique servant Ã  la
 * dÃ©matÃ©rialisation de l'administration. 
 *
 * Ce logiciel est rÃ©gi par la licence CeCILL soumise au droit franÃ§ais et
 * respectant les principes de diffusion des logiciels libres. Vous pouvez
 * utiliser, modifier et/ou redistribuer ce programme sous les conditions
 * de la licence CeCILL telle que diffusÃ©e par le CEA, le CNRS et l'INRIA 
 * sur le site "http://www.cecill.info".
 *
 * En contrepartie de l'accessibilitÃ© au code source et des droits de copie,
 * de modification et de redistribution accordÃ©s par cette licence, il n'est
 * offert aux utilisateurs qu'une garantie limitÃ©e.  Pour les mÃªmes raisons,
 * seule une responsabilitÃ© restreinte pÃ¨se sur l'auteur du programme,  le
 * titulaire des droits patrimoniaux et les concÃ©dants successifs.
 *
 * A cet Ã©gard  l'attention de l'utilisateur est attirÃ©e sur les risques
 * associÃ©s au chargement,  Ã  l'utilisation,  Ã  la modification et/ou au
 * dÃ©veloppement et Ã  la reproduction du logiciel par l'utilisateur Ã©tant 
 * donnÃ© sa spÃ©cificitÃ© de logiciel libre, qui peut le rendre complexe Ã  
 * manipuler et qui le rÃ©serve donc Ã  des dÃ©veloppeurs et des professionnels
 * avertis possÃ©dant  des  connaissances  informatiques approfondies.  Les
 * utilisateurs sont donc invitÃ©s Ã  charger  et  tester  l'adÃ©quation  du
 * logiciel Ã  leurs besoins dans des conditions permettant d'assurer la
 * sÃ©curitÃ© de leurs systÃ¨mes et ou de leurs donnÃ©es et, plus gÃ©nÃ©ralement, 
 * Ã l'utiliser et l'exploiter dans les mÃªmes conditions de sÃ©curitÃ©. 
 *
 * Le fait que vous puissiez accÃ©der Ã  cet en-tÃªte signifie que vous avez 
 * pris connaissance de la licence CeCILL, et que vous en avez acceptÃ© les
 * termes.
*/
?>
<?php
/**
 * \file public.ssl/admin/utilities/index.php
 * \brief Page d'accueil de la section utilitaires systèmes.
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 10.08.2006
 * 
 *
 * Cette page affiche des fonctions utilitaires
 * pour le site.
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */


// Configuration
require_once("../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');

$me = new User();

if (! $me->authenticate()) {
  $_SESSION["error"] = "Échec de l'authentification";
  header("Location: " . WEBSITE);
  exit();
}

if (! $me->isSuper()) {
  $_SESSION["error"] = "Accès refusé";
  header("Location: " . WEBSITE_SSL);
  exit();
}

$myAuthority = new Authority($me->get("authority_id"));

$caCerts = Helpers::getAuthorizedCACerts();

$doc = new HTMLLayout();

$doc->setTitle("Tedetis : Utilitaires");

$doc->buildMenu($me);

$html = "<div id=\"content\">\n";
$html .= "<h1>Utilitaires</h1>\n";
$html .= "<h2 class=\"toggle_title\" onclick=\"javascript:toggle_visibility('global_message');\">Envoi de message électronique global</h2>\n";
$html .= "<div id=\"global_message\" style=\"display: none;\">\n";
$html .= "<p>Utilisez le formulaire ci-dessous pour envoyer un message a l'ensemble des utilisateurs d'un module.</p>";
$html .= "<form action=\"" . WEBSITE_SSL . "/admin/utilities/admin_send_global_message.php\" method=\"post\" name=\"form\" onsubmit=\"return confirm('Voulez-vous vraiment envoyer le message à tous les utilisateurs de ce module');\">\n";
$html .= "Module concerné&nbsp;: " . $doc->getHTMLSelect("module", Module::getActiveModulesIdName(), null) . "<br /><br />\n";
$html .= "Sujet du message&nbsp;: <input type=\"text\" size=\"50\" maxlength=\"70\" name=\"subject\" /><br /><br />\n";
$html .= "Message (texte brut uniquement)&nbsp;:<br />\n";
$html .= "<textarea name=\"body\" cols=\"70\" rows=\"16\"></textarea><br /><br />\n";
$html .= "<input type=\"submit\" class=\"submit_button\" value=\"Envoyer le message\" />\n";
$html .= "</form>\n";
$html .= "</div>\n";
$html .= "<h2 class=\"toggle_title\" onclick=\"javascript:toggle_visibility('ca_list');\">Autorités de certification reconnues</h2>\n";
$html .= "<div id=\"ca_list\" style=\"display: none;\">\n";
$html .=  Helpers::$last_error;
$html .= "<p>Le TdT reconnaît les autorités de certification ci-dessous pour l'authentification des collectivités et la signature des fichiers&nbsp;:</p>\n";

if (count($caCerts) > 0) {
  $html .= "<ul>\n";
  $i = 0;

  foreach ($caCerts as $cert) {
	if (! empty($cert["subject"]["CN"])) {
	  $name = $cert["subject"]["CN"];
	} else {
	  $name = $cert["name"];
	}

	$html .= " <li class=\"toggle_title\" title=\"" . htmlspecialchars($cert["name"]) . "\" onclick=\"javascript:toggle_visibility('ca_cert_" . $i . "');\">" . htmlspecialchars($name) . "\n";
	$html .= "<dl id=\"ca_cert_" . $i . "\" style=\"display: none;\">\n";
	$html .= " <dt>Nom&nbsp;:</dt>\n";
	$html .= "  <dd>" . $cert["name"] . "</dd>\n";
	$html .= " <dt>Émetteur&nbsp;:</dt>\n";
	$html .= "  <dd>cn=" . $cert["issuer"]["CN"] . ",ou=" . $cert["issuer"]["OU"] . ",o=" . $cert["issuer"]["O"] . ",l=" . $cert["issuer"]["L"] . ",st=" . $cert["issuer"]["ST"] . ",c=" . $cert["issuer"]["C"] . "</dd>\n";
	$html .= " <dt>Haché&nbsp;:</dt>\n";
	$html .= "  <dd>" . $cert["hash"] . "</dd>\n";
	$html .= " <dt>Valide depuis&nbsp;:</dt>\n";
	$html .= "  <dd>" . date('d-m-Y H:i:s', $cert["validFrom_time_t"]) . "</dd>\n";
	$html .= " <dt>Valide jusqu'à&nbsp;:</dt>\n";
	$html .= "  <dd>" . date('d-m-Y H:i:s', $cert["validTo_time_t"]) . "</dd>\n";
	$html .= " <dt>Numéro de série&nbsp;:</dt>\n";
	$html .= "  <dd>" . $cert["serialNumber"] . "</dd>\n";
	$html .= "</dl>\n";
	$html .= "</li>\n";
	$i++;
  }

  $html .= "</ul>\n";
} else {
  $html .= "Pas de certificat d'autorité de certification trouvé.";
}

$html .= "</div>\n";
$html .= "</div>\n";

$doc->addBody($html);

$doc->buildFooter();

$doc->display();

?>
