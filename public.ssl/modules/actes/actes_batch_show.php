<?php
/*
 * TéDéTIS - Copyright 2006 Alternance-Soft
 * Contributeur : Jérôme Schell, Août 2006 
 *
 * contact@alternancesoft.com
 *
 * Ce logiciel est un programme informatique servant à la
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
 * \file actes_batch_show.php
 * \brief Page d'affichage d'un lot de fichier transaction Actes
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 13.02.2007
 * 
 *
 * Cette page affiche les détails d'un lot de transaction Actes et 
 * permet de demander sa suppression
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

// Configuration
require_once("../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');
require_once(SITEROOT . '/public.ssl/modules/actes/class/ActesBatch.class.php');

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

if (! $module->isActive() || ! $me->canAccess($module->get("name"))) {
  $_SESSION["error"] = "Accès refusé";
  header("Location: " . WEBSITE_SSL);
  exit();
}

$id = Helpers::getVarFromGet("id");

$myAuthority = new Authority($me->get("authority_id"));

$zeBatch = new ActesBatch();

if (isset($id) && ! empty($id)) {
  $zeBatch->setId($id);
  if ($zeBatch->init()) {
	$owner = new User($zeBatch->get("user_id"));
	$owner->init();
  } else {
	$_SESSION["error"] = "Erreur d'initialisation du lot.";
	header("Location: " . WEBSITE_SSL . "/modules/actes/actes_batch_handle.php");
	exit();
  }
} else {
  $_SESSION["error"] = "Pas d'identifiant de lot spécifié";
  header("Location: " . WEBSITE_SSL . "/modules/actes/actes_batch_handle.php");
  exit();
}

// Vérification des permissions
if (! $me->isSuper()) {
  if (! ($me->isAuthorityAdmin() && $me->get("authority_id") == $owner->get("authority_id")) && ($me->getId() != $owner->getId())) {
	$_SESSION["error"] = "Accès refusé";
	header("Location: " . WEBSITE_SSL . "/modules/actes/index.php");
	exit();
  }
}

$doc = new HTMLLayout();

$doc->setTitle("Tedetis : visualisation d'un lot");

$doc->buildMenu($me);

$html = "<div id=\"content\">\n";
$html .= "<center><a href=\"" . WEBSITE_SSL . "/modules/actes/actes_batch_handle.php\" class=\"bouton\">Retour liste lots</a></center>\n";
$html .= "<h2>Visualisation d'un lot</h2>\n";
$html .= "<div class=\"data_table\">\n";
$html .= "<table class=\"data\">\n";
$html .= $doc->getHTMLArrayline("Numéro du lot", $zeBatch->getId());
$html .= $doc->getHTMLArrayline("Description", htmlspecialchars($zeBatch->get("description")));
$html .= $doc->getHTMLArrayline("Préfixe numéro interne", htmlspecialchars($zeBatch->get("num_prefix")));
$html .= $doc->getHTMLArrayline("Date de création", Helpers::getDateFromBDDDate($zeBatch->get("submission_date"), true));
$html .= $doc->getHTMLArrayline("Nombre total de fichiers", $zeBatch->getAllFilesCount());
$html .= $doc->getHTMLArrayline("Nombre de fichiers traités", ($zeBatch->getAllFilesCount() - $zeBatch->getUnprocessedFilesCount()));
$html .= $doc->getHTMLArrayline("Nombre de fichiers restant à traiter", $zeBatch->getUnprocessedFilesCount());
$html .= "</table>\n";
$html .= "</div>\n";
$html .= "<br />\n";

// Fichiers contenus dans le lot
$html .= "<h3>Fichiers contenus dans le lot</h3>";
$html .= "<div class=\"data_table\">\n";
$html .= "<table class=\"file_list\">\n";
$html .= " <tr>\n";
$html .= "  <th>Fichier</th>\n";
$html .= "  <th>Taille</th>\n";
$html .= "  <th>Signature numérique</th>\n";
$html .= "  <th>Statut</th>\n";
$html .= "  <th>Traiter</th>\n";
$html .= " </tr>\n";

$batchFiles = $zeBatch->getBatchFiles();

if (is_array($batchFiles) && count($batchFiles) > 0) {
  foreach ($batchFiles as $batchFile) {
	$html .= " <tr>\n";
	$html .= "  <td class=\"long_field\">";	
	$html .= ($batchFile->isProcessed()) ? $batchFile->getDisplayName() : "<a href=\"" . WEBSITE_SSL . "/modules/actes/actes_download_file.php?file=" . $batchFile->getId() . "&amp;type=batch\" title=\"Télécharger le fichier\">" . htmlspecialchars($batchFile->getDisplayName()) . "</a>";
	$html .= "</td>\n";
	$html .= "  <td>" . $batchFile->get("filesize") . "</td>\n";
	$html .= "  <td>";
	$html .= (strlen($batchFile->get("signature")) > 0) ? "Présente" : "Non présente";
	$html .= "</td>\n";
	$html .= "  <td>";
	$html .= ($batchFile->isProcessed()) ? "<a href=\"" . WEBSITE_SSL . "/modules/actes/actes_transac_show.php?id=" . $batchFile->get("transaction_id") . "\" title=\"Voir la transaction issue de ce fichier\">Traité</a>" : "Non traité";
	$html .= "</td>\n";
	$html .= "  <td><a href=\"" . WEBSITE_SSL . "/modules/actes/actes_transac_add.php?batchfile=" . $batchFile->getId() . "\" class=\"icon\" title=\"Créer la transaction correspondant à ce fichier\"><img src=\"" . WEBSITE_SSL . "/custom/images/erreur.png\" alt=\"Icone traitement\" /></a></td>\n";
	$html .= " </tr>\n";
  }
} else {
  $html .= " <tr>\n";
  $html .= "  <td colspan=\"3\">Pas de fichier trouvé</td>";
  $html .= " </tr>\n";
}

$html .= "</table>\n";
$html .= "</div>\n";
$html .= "<form action=\"" . WEBSITE_SSL . "/modules/actes/actes_batch_delete.php\" onsubmit=\"return confirm('Voulez-vous vraiment supprimer définitivement ce lot ?')\" method=\"post\">\n";
$html .= "<input type=\"hidden\" name=\"id\" value=\"" . $zeBatch->getId(). "\" />\n";
$html .= "<input type=\"submit\" value=\"Supprimer ce lot\" class=\"bouton-danger\" />\n";
$html .= "</form>\n";
$html .= "</div>\n";

$doc->addBody($html);

$doc->buildFooter();

$doc->display();

?>
