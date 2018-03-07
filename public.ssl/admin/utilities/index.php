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



$caCerts = Helpers::getAuthorizedCACerts(EXTENDED_VALIDCA_PATH);

$doc = new HTMLLayout();

$doc->setTitle("Tedetis : Utilitaires");

$doc->openContainer();
$doc->openSideBar();
$doc->buildMenu($me);
$doc->closeSideBar();
$doc->openContent();



$html .= "<h1>Utilitaires</h1>\n";


ob_start()
?>
<div id="actions_area">
	<h2>Action</h2>
	<a class="btn btn-primary" href='/admin/utilities/certificate_list.php'>Liste des certificats</a>
	<a class="btn btn-primary" href='/admin/utilities/libersign.php'>Libersign</a>
    <a class="btn btn-danger" href='/admin/utilities/send-critical-message.php'>Déclencher une erreur critique (test)</a>
</div>


<?php
$html .= ob_get_contents();
ob_end_clean();

$html .= "<h2 >Envoi de message électronique global</h2>\n";
$html .= "<div id=\"global_message\">\n";
$html .= "<p>Utilisez le formulaire ci-dessous pour envoyer un message a l'ensemble des utilisateurs d'un module.</p>";
$html .= "<form action=\"" . WEBSITE_SSL . "/admin/utilities/admin_send_global_message.php\" method=\"post\" name=\"form\" onsubmit=\"return confirm('Voulez-vous vraiment envoyer le message à tous les utilisateurs de ce module');\">\n";
$html .= "Module concerné&nbsp;: " . $doc->getHTMLSelect("module", Module::getActiveModulesIdName(), null) . "<br /><br />\n";
$html .= "Sujet du message&nbsp;: <input type=\"text\" size=\"50\" maxlength=\"70\" name=\"subject\" /><br /><br />\n";
$html .= "Message (texte brut uniquement)&nbsp;:<br />\n";
$html .= "<textarea name=\"body\" cols=\"70\" rows=\"16\"></textarea><br /><br />\n";
$html .= "<input type=\"submit\" class=\"submit_button\" value=\"Envoyer le message\" />\n";
$html .= "</form>\n";
$html .= "</div>\n";

$doc->addBody($html);

$doc->closeContent();
$doc->closeContainer();

$doc->buildFooter();

$doc->display();

?>
