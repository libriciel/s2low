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

/**
 * \file public.ssl/modules/actes/actes_batch_handle.php
 * \brief Page d'accueil du traitement par lots
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 02.02.2007
 * 
 *
 * Cette page affiche la liste des transactions du module
 * ACTES et permet de les modifier ou d'en créer de nouvelles
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

// Configuration
require_once ("../../../config/config.php");
require_once (SITEROOT . '/class/include.class.php');
require_once (SITEROOT . '/public.ssl/modules/actes/class/ActesBatch.class.php');

// Instanciation du module courant
$module = new Module();
if (!$module->initByName("actes")) {
  $_SESSION["error"] = "Erreur d'initialisation du module";
  header("Location: " . WEBSITE_SSL);
  exit ();
}

$me = new User();

if (!$me->authenticate()) {
  $_SESSION["error"] = "Échec de l'authentification";
  header("Location: " . WEBSITE);
  exit ();
}

if (!$module->isActive() || !$me->canAccess($module->get("name"))) {
  $_SESSION["error"] = "Accès refusé";
  header("Location: " . WEBSITE_SSL);
  exit ();
}

$myAuthority = new Authority($me->get("authority_id"));

$zeBatch = new ActesBatch();

$batchesList = $zeBatch->getBatchesListForUser($me->getId());

$doc = new HTMLLayout();

$js = "<script type=\"text/javascript\">\n";
$js .= "  function redirect_to_create_form(select_form) {\n";
$js .= "    batch_file_id = select_form.options[select_form.selectedIndex].value;\n";
$js .= "    document.location='" . WEBSITE_SSL . "/modules/actes/actes_transac_add.php?batchfile=' + batch_file_id;\n";
$js .= "  }\n";
$js .= "</script>\n";

$doc->addHeader($js);

$doc->setTitle("Tedetis : Traitement par lots module actes");

$doc->buildMenu($me);

$html = "<div id=\"content\">\n";
$html .= "<h1>ACTES - Traitement par lots</h1>\n";

if (! $me->isSuper() && $me->canEdit($module->get('name'))) {
  $html .= "<div id=\"actions_area\">\n";
  $html .= "<h2>Actions</h2>\n";
  $html .= "<a href=\"" . WEBSITE_SSL . "/modules/actes/actes_batch_add.php\" class=\"bouton\">Créer un nouveau lot</a>\n";
  $html .= "</div>\n";
}

$html .= "<h2>Liste des lots de transactions</h2>\n";

if (is_array($batchesList) && count($batchesList) > 0) {
  $html .= "<div class=\"data_table\">\n";
  $html .= "<table cellpadding=\"3\" cellspacing=\"2\" class=\"data\">";
  $html .= "<tr>\n";
  $html .= " <th class=\"data\">Lot</th>\n";
  $html .= " <th class=\"data\">Description</th>\n";
  $html .= " <th class=\"data\">Date de création</th>\n";
  $html .= " <th class=\"data\">Fichiers restants</th>\n";
  $html .= " <th class=\"data\">Traiter le fichier&nbsp;:</th>\n";
  $html .= "</tr>\n";

  $i = 0;

  foreach ($batchesList as $batchData) {
	$batch = new ActesBatch($batchData["id"]);
	$batch->init();

	$html .= "<tr class=\"alternate" . ($i + 1) . "\">\n";
	$html .= " <td><a href=\"" . WEBSITE_SSL . "/modules/actes/actes_batch_show.php?id=" . $batch->getId() . "\" title=\"Visualiser les détails du lot n°" . $batch->getId() . "\">" . htmlspecialchars($batch->getId()) . "</a></td>\n";
	$html .= " <td>" . htmlspecialchars($batch->get("description")) . "</td>\n";
	$html .= " <td>" . Helpers::getDateFromBDDDate($batch->get("submission_date"), true) . "</td>\n";
	$html .= " <td>" . $batch->getUnprocessedFilesCount() . "</td>\n";
	$html .= " <td class=\"long_field\">";

	if ($batch->getUnprocessedFilesCount() > 0) {
	  $html .= $doc->getHTMLSelect("batch_files", $batch->getUnprocessedFilesIdName(), null, " onchange=\"javascript:redirect_to_create_form(this);\"");
	} else {
	  $html .= "<form action=\"" . WEBSITE_SSL . "/modules/actes/actes_batch_delete.php\" onsubmit=\"return confirm('Voulez-vous vraiment supprimer définitivement ce lot ?')\" method=\"post\">\n";
	  $html .= "<p>Tous les fichiers sont traités&nbsp;:\n";
	  $html .= "<input type=\"hidden\" name=\"id\" value=\"" . $batch->getId(). "\" />\n";
	  $html .= "<input type=\"submit\" value=\"Supprimer le lot\" />\n";
	  $html .= "</p></form>\n";
	}

	$html .= "</td>\n";
	$html .= "</tr>\n";
	
	$i = ($i + 1) % 2;
  }

  $html .= "</table>\n";
  $html .= "</div>\n";
} else {
  $html .= "Pas de lot trouvé.";
}

$html .= "</div>\n";

$doc->buildPager($zeBatch);

$doc->addBody($html);

$doc->buildFooter();

$doc->display();
?>