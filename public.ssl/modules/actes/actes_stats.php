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
 * \file actes_stats.php
 * \brief Page d'affichage des statistiques sur le module ACTES
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 25.08.2006
 * 
 *
 * Cette page affiche les statistiques de volumétrie sur le
 * module Actes.
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

// Configuration
require_once("../../../config/config.php");
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

if (! $module->isActive() || ! $me->canAccess($module->get("name"))) {
  $_SESSION["error"] = "Accès refusé";
  header("Location: " . WEBSITE_SSL);
  exit();
}

$myAuthority = new Authority($me->get("authority_id"));

// Mois en cours
$currentMonth = date('Y-m-01 00:00:00');
// Année en cours
$currentYear = date('Y-01-01 00:00:00');

// Récupération de la liste des enveloppes en fonction de l'utilisateur en cours
$filter = array();

if ($me->isAuthorityAdmin()) {
  $filter[] = "users.authority_id=" . $me->get("authority_id");
}
//---- corrige bug 209 par TAN
// ajoute le statistique de group admin
elseif($me->isGroupAdmin())
{
  $filter[]="users.authority_group_id=".$me->get("authority_group_id");
}
//-----
 elseif (! $me->isAdmin()) {
  $filter[] = "users.id=" . $me->getId();
}

// Enveloppes depuis toujours
$allEnv = ActesEnvelope::getEnvelopesId(implode(" AND ", $filter));
$allEnvTrans = ActesEnvelope::getEnvelopesId(implode(" AND ", $filter), true);
$allVol = ActesEnvelope::getEnvelopesVolume(implode(" AND ", $filter));
$allVolTrans = ActesEnvelope::getEnvelopesVolume(implode(" AND ", $filter), true);

// Enveloppes du mois
$monthFilter = array_merge($filter, array("actes_envelopes.submission_date >= '" . $currentMonth . "'"));
$monthEnv = ActesEnvelope::getEnvelopesId(implode(" AND ", $monthFilter));
$monthEnvTrans = ActesEnvelope::getEnvelopesId(implode(" AND ", $monthFilter), true);
$monthVol = ActesEnvelope::getEnvelopesVolume(implode(" AND ", $monthFilter));
$monthVolTrans = ActesEnvelope::getEnvelopesVolume(implode(" AND ", $monthFilter), true);

// Enveloppes de l'année
$yearFilter = array_merge($filter, array("actes_envelopes.submission_date >= '" . $currentYear . "'"));
$yearEnv = ActesEnvelope::getEnvelopesId(implode(" AND ", $yearFilter));
$yearEnvTrans = ActesEnvelope::getEnvelopesId(implode(" AND ", $yearFilter), true);
$yearVol = ActesEnvelope::getEnvelopesVolume(implode(" AND ", $yearFilter));
$yearVolTrans = ActesEnvelope::getEnvelopesVolume(implode(" AND ", $yearFilter), true);

$doc = new HTMLLayout();

$doc->setTitle("Tedetis : Actes - Statistiques");

$doc->buildMenu($me);

$html = "";
$html .= "<div id=\"content\">\n";
$html .= "<h1>ACTES - Dématérialisation du contrôle de légalité</h1>\n";
$html .= "<h2>Statistiques de transmission des enveloppes";

if ($me->isSuper()) {
  $html .= " pour l'ensemble des collectivités/utilisateurs";
} elseif ($me->isAuthorityAdmin()) {
  $html .= " pour la collectivité " . $myAuthority->get("name");
} 
//-----ajouté par TAN ,corriger le bug 209
elseif ($me->isGroupAdmin())
{
    $TempGroup=new Group($me->get("authority_group_id"));
    $TempGroup->init();
	$html .="pour le Group ".$TempGroup->get("name");
}
//------
else {
  $html .= " pour l'utilisateur " . $me->getPrettyName();
}

$html .= "</h2>\n";
$html .= "<div class=\"list_form\">\n";
$html .= " <dl>\n";
$html .= "  <dt>Depuis le début du mois&nbsp;:</dt>\n";
$html .= "   <dd><ul>\n";
$html .= "    <li>Nombre d'enveloppes postées sur le tdt&nbsp;: " . count($monthEnv) . "</li>\n";
$html .= "    <li>Nombre d'enveloppes transmises au ministère&nbsp;: " . count($monthEnvTrans) . "</li>\n";
$html .= "    <li>Volume des enveloppes postées sur le tdt&nbsp;: " . (($monthVol) ? $monthVol : "0") . " octets</li>\n";
$html .= "    <li>Volume des enveloppes transmises au ministère&nbsp;: " . (($monthVolTrans) ? $monthVolTrans : "0") . " octets</li>\n";
$html .= "   </ul></dd>\n";
$html .= "  <dt>Depuis le début de l'année&nbsp;:</dt>\n";
$html .= "   <dd><ul>\n";
$html .= "    <li>Nombre d'enveloppes postées sur le tdt&nbsp;: " . count($yearEnv) . "</li>\n";
$html .= "    <li>Nombre d'enveloppes transmises au ministère&nbsp;: " . count($yearEnvTrans) . "</li>\n";
$html .= "    <li>Volume des enveloppes postées sur le tdt&nbsp;: " . (($yearVol) ? $yearVol : "0") . " octets</li>\n";
$html .= "    <li>Volume des enveloppes transmises au ministère&nbsp;: " . (($yearVolTrans) ? $yearVolTrans : "0") . " octets</li>\n";
$html .= "   </ul></dd>\n";
$html .= "  <dt>En totalité&nbsp;:</dt>\n";
$html .= "   <dd><ul>\n";
$html .= "    <li>Nombre d'enveloppes postées sur le tdt&nbsp;: " . count($allEnv) . "</li>\n";
$html .= "    <li>Nombre d'enveloppes transmises au ministère&nbsp;: " . count($allEnvTrans) . "</li>\n";
$html .= "    <li>Volume des enveloppes postées sur le tdt&nbsp;: " . (($allVol) ? $allVol : "0") . " octets</li>\n";
$html .= "    <li>Volume des enveloppes transmises au ministère&nbsp;: " . (($allVolTrans) ? $allVolTrans : "0") . " octets</li>\n";
$html .= "   </ul></dd>\n";
$html .= " </dl>\n";
$html .= "</div>\n";
$html .= "</div>\n";

$doc->addBody($html);

$doc->buildFooter();

$doc->display();
?>