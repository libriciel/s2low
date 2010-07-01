<?php
/*
 * TéDéTIS - Copyright 2006 Alternance-Soft
 * Contributeur : Jérôme Schell, Août 2006 
 *
 * contact@alternancesoft.com
 *
 * Ce logiciel est un programme informatique servant à   la
 * dématérialisation de l'administration. 
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
 * associés au chargement,  à   l'utilisation,  à   la modification et/ou au
 * développement et à   la reproduction du logiciel par l'utilisateur étant 
 * donné sa spécificité de logiciel libre, qui peut le rendre complexe à   
 * manipuler et qui le réserve donc à   des développeurs et des professionnels
 * avertis possédant  des  connaissances  informatiques approfondies.  Les
 * utilisateurs sont donc invités à   charger  et  tester  l'adéquation  du
 * logiciel à   leurs besoins dans des conditions permettant d'assurer la
 * sécurité de leurs systèmes et ou de leurs données et, plus généralement, 
 * à  l'utiliser et l'exploiter dans les mêmes conditions de sécurité. 
 *
 * Le fait que vous puissiez accéder à cet en-tte signifie que vous avez 
 * pris connaissance de la licence CeCILL, et que vous en avez accepté les
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
