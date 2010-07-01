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
 * \file select_popup.php
 * \brief Page pour la sélection d'attributs des collectivités
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 01.08.2006
 * 
 *
 * Cette page affiche une liste d'attributs dans lesquels choisir
 * celui voulu. Doit être appelé depuis une autre page.
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

// Configuration
require_once("../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');

$me = new User();

if (! $me->authenticate()) {
  $_SESSION["error"] = "Éhec de l'authentification";
  header("Location: " . WEBSITE);
  exit();
}

$type = Helpers::getVarFromGet("type", true);

$myAuthority = new Authority($me->get("authority_id"));

$doc = new HTMLLayout("xhtml_simple.tpl.php");

$doc->setTitle("Tedetis : sélection attribut");

$html = "<div id=\"attribute_list\">\n";

switch ($type) {
 case 'authority_type':
   if (! $me->isAdmin()) {
	 $_SESSION["error"] = "Accès refusé";
	 header("Location: " . WEBSITE_SSL);
	 exit();
   }

   $types = Authority::getAuthorityTypes();

   $html .= "<dl>\n";

   foreach ($types as $type) {
	 if ($type["type"] == 'parent') {
	   $html .= "<dt>" . $type["code"] . "&nbsp;-&nbsp;" . $type["description"] . "</dt>\n";
	 } elseif ($type["type"] == 'child') {
	   $html .= "<dd><a href=\"#tedetis\" onclick=\"javascript:return_choice('" . $type["code"] . "', '" . addslashes($type["description"]) . "');\">" . $type["code"] . "&nbsp;-&nbsp;" . $type["description"] . "</a></dd>\n";
	 }
   }

   $html .= "</dl>\n";

   $js = "<script type=\"text/javascript\">\n";
   $js .= "function return_choice(value, text) {\n";
   $js .= "  window.opener.document.getElementById('authority_type').value = value;\n";
   $js .= "  window.opener.document.getElementById('authority_type_text').innerHTML = value + '&nbsp;-&nbsp;' + text;\n";
   $js .= "  window.close();\n";
   $js .= "}\n";
   $js .= "</script>\n";
   break;

 case 'department':
   if (! $me->isAdmin()) {
	 $_SESSION["error"] = "Accès refusé";
	 header("Location: " . WEBSITE_SSL);
	 exit();
   }

   $depts = Authority::getDepartmentsList();

   $html .= "<dl>\n";

   foreach ($depts as $dept) {
	 $html .= "<dt>" . $dept["code"] . "&nbsp;-&nbsp;" . $dept["name"] . "</dt>\n";

	 $districts = Authority::getDistrictsForDepartment($dept["code"]);

	 foreach ($districts as $code => $district) {
	   $html .= "<dd><a href=\"#tedetis\" onclick=\"javascript:return_choice('" . addslashes($dept["code"]) . "', '" . addslashes($code) . "', '" . addslashes($dept["name"]) . "&nbsp;/&nbsp;" . addslashes($district) . "');\">" . $code . "&nbsp;-&nbsp;" . $district . "</a></dd>\n";
	 }
   }

   $html .= "</dl>\n";

   $js = "<script type=\"text/javascript\">\n";
   $js .= "function return_choice(dept, distr, text) {\n";
   $js .= "  window.opener.document.getElementById('department').value = dept;\n";
   $js .= "  window.opener.document.getElementById('district').value = distr;\n";
   $js .= "  window.opener.document.getElementById('department_text').innerHTML = text;\n";
   $js .= "  window.close();\n";
   $js .= "}\n";
   $js .= "</script>\n";
   break;

 case 'classification':
   require_once(SITEROOT . "/public.ssl/modules/actes/class/ActesClassification.class.php");
   $classifications = ActesClassification::getClassificationList($myAuthority->getId());

   $done = array();

   if (count($classifications) > 0) {
	 foreach ($classifications as $key => $classification) {
	   $codes = array();
	   $items = array();
	   $item = $classification;
	   $level = 1;

	   while ($item) {
		 if (! $done[$item["id"]]) {
		   // Item non encore traité
		   // On l'ajoute sur la pile
		   array_push($codes, $item["code"]);
		   // On l'affiche
		   $html .= "<a class=\"tree_level_" . $level . "\" href=\"#tedetis\" onclick=\"javascript:return_choice('" . implode(".", $codes) . "&nbsp;-&nbsp;" . str_replace('"', "&quot;", str_replace("'", "\\'", $item["description"])) . "', " . implode(",", $codes) . ");\">" . implode(".", $codes) . "&nbsp;-&nbsp;" . $item["description"] . "</a><br />\n";
		   // On le marque comme traité
		   $done[$item["id"]] = true;
		 }
		 
		 // Traitement des items enfants de l'item courant
		 if (isset($item["children_id"]) && count($item["children_id"]) > 0) {
		   $par_item = $item;
		   $level++;
		   // On passe au prochain enfant de l'item courant en le supprimant du tableau des enfants
		   $item = $classifications[array_shift($par_item["children_id"])];
		   // On ajoute l'item parent sur la pile des items pour continuer le traitement des enfants lors de la remontée
		   array_push($items, $par_item);
		 } else {
		   // Plus d'enfant => on remonte la pile des items
		   $item = array_pop($items);
		   // On remonte le code courant également
		   array_pop($codes);
		   $level--;
		 }
	   }
	 }

	 $js = "<script type=\"text/javascript\">\n";
	 $js .= "function return_choice(text) {\n";
	 $js .= "  args = return_choice.arguments;\n\n";
	 $js .= "  for (i = 1; i <= 5; i++) {\n";
	 $js .= "    zeVar = 'classif' + i;\n";
	 $js .= "    elt = window.opener.document.getElementById(zeVar);\n";
	 $js .= "    if (args[i]) {\n";
	 $js .= "      elt.value = args[i];\n";
	 $js .= "    } else {\n";
	 $js .= "      elt.value = '';\n";
	 $js .= "    }\n";
	 $js .= "  }\n";
	 $js .= "  window.opener.document.getElementById('classification_text').innerHTML = text;\n";
	 $js .= "  window.close();\n";
	 $js .= "}\n";
	 $js .= "</script>\n";
   } else {
	 $html .= "Pas de classification matières/sous-matières associée à votre collectivité.<br />\n";
	 $html .= "Utilisez le bouton «&nbsp;Mettre à jour la classification&nbsp;» de l'interface de création de transaction pour effectuer une demande de récupération de la classification.<br />";
	 $js = "";
   }

   break;

 default:
   echo 'Appel incorrect';
   exit();
}

 
$html .= "</div>\n";
$html .= "<a href=\"#tedetis\" onclick=\"javascript:window.close();\">Fermer la fenêtre</a>\n";

$doc->addBody($html);

$doc->addHeader($js);

$doc->display();

?>