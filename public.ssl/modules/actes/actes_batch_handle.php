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