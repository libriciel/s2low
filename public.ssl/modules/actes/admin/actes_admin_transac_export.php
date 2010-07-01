<?php
/*
 * TéDéTIS - Copyright 2006 Alternance-Soft
 * Contributeur : Jérôme Schell, Août 2006 
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
 * \file actes_admin_transac_export.php
 * \brief Page d'export de la liste des transactions au format CSV
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 10.08.2006
 * 
 *
 * Cette page effectue l'extraction de l'ensemble des transactions
 * envoyées au MIAT et génère un fichier CSV.
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

// Configuration
require_once("../../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');
require_once(SITEROOT . '/public.ssl/modules/actes/class/ActesEnvelope.class.php');

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

if (! $me->isSuper()) {
  $_SESSION["error"] = "Accès refusé";
  header("Location: " . WEBSITE_SSL);
  exit();
}

if (! $module->isActive()|| ! $me->canAccess($module->get("name"))) {
  $_SESSION["error"] = "Accès refusé";
  header("Location: " . WEBSITE_SSL);
  exit();
}

$history = ActesEnvelope::getEnvelopesHistory();

$doc = new CSVLayout();
$doc->addHeader("Date de transmission;Heure de transmission;Nom du fichier (.tar.gz.) transmis;Nom des fichiers contenus dans le fichier .tar.gz. transmis;SIREN de la collectivité émettrice;Département de la collectivité;Arrondissement de la collectivité");

if (count($history) > 0) {
  foreach ($history as $env) {
	$entry = array();
	// Récupération de la liste des fichiers pour cette enveloppe
	$files = ActesEnvelope::getEnvelopesIncludedFiles($env["id"]);
	$timestamp = Helpers::getTimestampFromBDDDate($env["date"]);

	// Date de transmission
	$entry[] = date("d-m-Y", $timestamp);
	// Heure de transmission
	$entry[] = date("H:i:s", $timestamp);
	// Nom du .tar.gz
	$entry[] = basename($env["file_path"]);
	// Nom des fichiers contenus dans l'archive
	$filenames = array();
	foreach ($files as $file) {
	  $filenames[] = $file["filename"];
	}

	$entry[] = "\"" . implode("|", $filenames) . "\"";
	// SIREN de la collectivité
	$entry[] = $env["siren"];
	// Département de la collectivité
	$entry[] = "\"" . $env["department"] . "\"";
	// Arrondissement de la collectivité
	$entry[] = "\"" . $env["district"] . "\"";

	$doc->addLine($entry);
  }
}

$doc->display();

?>