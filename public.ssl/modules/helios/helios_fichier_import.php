<?php
/*
 * TéDéTIS - Copyright 2006 Alternance-Soft
 * Contributeur : C. Pop Mars 2007
 *
 * contact@alternancesoft.com
 *
 * Ce logiciel est un programme informatique servant à la
 * dématèrialisation de l'administration. 
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
 * associés au chargement,  à l'utilisation,  à la modification et/ou au
 * développement et à la reproduction du logiciel par l'utilisateur étant 
 * donné sa spécificité de logiciel libre, qui peut le rendre complexe à 
 * manipuler et qui le réserve donc à des développeurs et des professionnels
 * avertis possédant  des  connaissances  informatiques approfondies.  Les
 * utilisateurs sont donc invités à charger  et  tester  l'adéquation  du
 * logiciel à leurs besoins dans des conditions permettant d'assurer la
 * sécurité de leurs systèmes et ou de leurs données et, plus généralement, 
 * à l'utiliser et l'exploiter dans les mêmes conditions de sécurité. 
 *
 * Le fait que vous puissiez accéder à cet en-tête signifie que vous avez 
 * pris connaissance de la licence CeCILL, et que vous en avez accepté les
 * termes.
*/
?>
<?php

/**
 * \file helios_transac_import.php
 * \brief Page permettannt l'import d'un fichier
 * \author Cristina Pop <cpop@alternancesoft.com>, Jerome Schell <j.schell@alternancesoft.com>
 * \date 11.03.2007
 * 
 *
 * Cette page affiche un formulaire permettant d'importer
 * un fichier qui contieznt un message concernant un document financier.
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

// Configuration
require_once ("../../../config/config.php");
require_once (SITEROOT . '/class/include.class.php');

// Instanciation du module courant
$module = new Module();
if (!$module->initByName("helios")) {
  $_SESSION["error"] = "Erreur d'initialisation du module";
  header("Location: " . WEBSITE_SSL);
  exit ();
}

$me = new User();

if (!$me->authenticate()) {
  $_SESSION["error"] = "échec de l'authentification";
  header("Location: " . WEBSITE);
  exit ();
}

if ($me->isGroupAdminOrSuper() || !$module->isActive() || !$me->canEdit($module->get("name"))) {
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

/*TODO :
 * il faut ajouter un méthode vérifier le tempon est saturé ou pas. 
 * si OUI; on est obligé de bloquer cd page.
 */
/*  
 // on pas pas besoin de le vérifier du côté de php
// set up a connection 

if (HELIOS_SVN)
{
	$conn_id = ftp_connect(HELIOS_FTP_SERVER,HELIOS_FTP_PORT);
	if (!$conn_id)
	{
		$_SESSION["error"] = "Etablissment de la connection FTP par VPN a echoué.";
	  header("Location: " . WEBSITE_SSL);
	  exit();
	}
	// try to login
	else if (!@ftp_login($conn_id, $myAuthority->get("helios_ftp_login"), $myAuthority->get('helios_ftp_password'))) {
	  $_SESSION["error"] = "VPN ou FTP login error,Vérifier avec administrateur";
	  header("Location: " . WEBSITE_SSL);
	  exit ();
	}
}
else
{
	$conn_id = ftp_connect(HELIOS_FTP_SERVER,HELIOS_FTP_PORT);
	if (!$conn_id)
	{
		$_SESSION["error"] = "Etablissment de la connection FTP par VPN a echoué.";
	  header("Location: " . WEBSITE_SSL);
	  exit();
	}
	// try to login
	else if (!@ftp_login($conn_id, HELIOS_FTP_LOGIN, HELIOS_FTP_PASSWORD)) {
	  $_SESSION["error"] = "VPN ou FTP login error,Vérifier avec administrateur";
	  header("Location: " . WEBSITE_SSL);
	  exit ();
	}
}
*/
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

// Zone contenu 
//TO DO : imposer une taille max. pour le fichier
$html .= "<div id=\"content\">\n";
$html .= "<h1>Helios - Dématérialisation de documents comptables</h1>\n";
$html .= "<center><a href=\"" . WEBSITE_SSL . "/modules/helios/\" class=\"bouton\">Retour liste transactions</a></center>\n";
$html .= "<h2>Import d'un fichier</h2>\n";
$html .= "<form method=\"POST\" enctype=\"multipart/form-data\" ";
$html .= " action=\"" . WEBSITE_SSL . "/modules/helios/helios_script_reception.php\" > ";
$html .= "<table  style='text-align:right''>";
$html .= "<tr><td>Fichier XML : </td><td><input type=\"FILE\" name=\"enveloppe\"/></td></tr>";
//$html .="<tr><td>Fichier de signature (optionnel) : </td><td><input type=\"FILE\" name=\"signature\"/> </td></tr>";
$html .= "</table>";
$html .= "<input class=\"submit_button\" type=\"submit\" value=\" Importer un fichier\" >";
$html .= "</form>";

//pour le test de l'API

$html .= "<h2>Teste (API) de récuperation du status du fichier à partir de la transaction</h2>\n";
$html .= "<form method=\"GET\"";
$html .= " action=\"" . WEBSITE_SSL . "/modules/helios/api/helios_transac_get_status.php\" > ";
$html .= "<input type=\"text\" name=\"transaction\" value=\"0\"/> <br>";
$html .= "<input class=\"submit_button\" type=\"submit\" value=\"Recuperer le status crt\" >";
$html .= "</form>";
$html .= "<p>---------------------</p>";
$html .= '<form method="POST" enctype="multipart/form-data" ';
$html .= ' action="' . WEBSITE_SSL . '/modules/helios/api/helios_importer_fichier.php" > ';
$html .= '<input type="FILE" name="enveloppe" /> <br>';
$html .= "<input class=\"submit_button\" type=\"submit\" value=\"Importer un fichier\" >";
$html .= "</form>";
$html .= "<p>---------------------</p>";
$html .= '<form method="POST"';
$html .= ' action="' . WEBSITE_SSL . '/modules/helios/api/helios_get_retour.php" > ';
$html .= '<input type="text" name="id" /> <br>';
$html .= '<input class="submit_button" type="submit" value="Recuperer le fichier de PES_RETOUR" >';
$html .= "</form>";
$html .= "<p>---------------------</p>";
$html .= '<form method="POST" ';
$html .= ' action="' . WEBSITE_SSL . '/modules/helios/api/helios_get_list.php" > ';
$html .= '<input class="submit_button" type="submit" value="Recuperer la lister de PES_RETOUR" >';
$html .= "</form>";
$html .= "<p>---------------------</p>";
$html .= '<form method="POST" ';
$html .= ' action="' . WEBSITE_SSL . '/modules/helios/api/helios_change_status.php" > ';
$html .= '<input type="text" name="id" /> <br>';
$html .= "<input class=\"submit_button\" type=\"submit\" value=\"changer le status d'un message PES_RETOUR de nonlu ver lu\" >";
$html .= "</form>";

// sf teste

$html .= "</div>\n";

$doc->addBody($html);

$doc->buildFooter();

$doc->display();
?>
