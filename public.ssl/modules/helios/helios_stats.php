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
require_once(SITEROOT . '/public.ssl/modules/helios/class/HeliosTransaction.class.php');

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


// Récupération de la liste des enveloppes en fonction de l'utilisateur en cours

if ($me->isAuthorityAdmin()) {
  $author_filter= "AND users.authority_id=" . $me->get("authority_id");;
}
//---- corrige bug 209 par TAN
// ajoute le statistique de group admin
elseif($me->isGroupAdmin())
{
  $author_filter=" AND users.authority_group_id=".$me->get("authority_group_id");
}
//-----
 elseif (! $me->isAdmin()) {
  $author_filter = " AND users.id=" . $me->getId();
}

// Transactions depuis toujours
$allTrans= HeliosTransaction::countTransactions($author_filter);
$allTransmitted = HeliosTransaction::countTransactions($author_filter,true);
$allVol = HeliosTransaction::countTransactionVol($author_filter);
$allVolTransmitted =  HeliosTransaction::countTransactionVol($author_filter,true);

// Transactions du mois
$monthTrans = HeliosTransaction::countTransactions($author_filter,false,true);
$monthTransmitted = HeliosTransaction::countTransactions($author_filter,true,true);
$monthVol = HeliosTransaction::countTransactionVol($author_filter,false,true);
$monthVolTransmitted= HeliosTransaction::countTransactionVol($author_filter,true,true);

// transactions de l'année
$yearTrans = HeliosTransaction::countTransactions($author_filter,false,false,true);
$yearTransmitted = HeliosTransaction::countTransactions($author_filter,true,false,true);
$yearVol = HeliosTransaction::countTransactionVol($author_filter,false,false,true);
$yearVolTransmitted = HeliosTransaction::countTransactionVol($author_filter,true,false,true);

$doc = new HTMLLayout();

$doc->setTitle("Tedetis : Hélios - Statistiques");

$doc->buildMenu($me);

$html .= "<div id=\"content\">\n";
$html .= "<h1>HELIOS - Dématérialisation de documents financiers</h1>\n";
$html .= "<h2>Statistiques des transactions Hélios";

if ($me->isSuper()) 
{
		$html .= " pour l'ensemble des collectivités/utilisateurs";
} 
elseif ($me->isAuthorityAdmin()) 
{
  	$html .= " pour la collectivité " . $myAuthority->get("name");
} 
elseif ($me->isGroupAdmin())
{
    $TempGroup=new Group($me->get("authority_group_id"));
    $TempGroup->init();
		$html .="pour le Group ".$TempGroup->get("name");
}
else 
{
  $html .= " pour l'utilisateur " . $me->getPrettyName();
}

$html .= "</h2>\n";
$html .= "<div class=\"list_form\">\n";
$html .= " <dl>\n";
$html .= "  <dt>Depuis le début du mois&nbsp;:</dt>\n";
$html .= "   <dd><ul>\n";
$html .= "    <li>Nombre de transaction postées sur le tdt&nbsp;: " . $monthTrans . "</li>\n";
$html .= "    <li>Nombre de transaction transmises à Hélios&nbsp;: " .$monthTransmitted . "</li>\n";
$html .= "    <li>Volume des transactions postées sur le tdt&nbsp;: " .$monthVol. " octets</li>\n";
$html .= "    <li>Volume des transactions transmises à Hélios&nbsp;: " . $monthVolTransmitted. " octets</li>\n";
$html .= "   </ul></dd>\n";
$html .= "  <dt>Depuis le début de l'année&nbsp;:</dt>\n";
$html .= "   <dd><ul>\n";
$html .= "    <li>Nombre de transaction postées sur le tdt&nbsp;: " . $yearTrans . "</li>\n";
$html .= "    <li>Nombre de transaction transmises à Hélios&nbsp;: " .$yearTransmitted . "</li>\n";
$html .= "    <li>Volume des transactions postées sur le tdt&nbsp;: " .$yearVol. " octets</li>\n";
$html .= "    <li>Volume des transactions transmises à Hélios&nbsp;: " . $yearVolTransmitted. " octets</li>\n";
$html .= "   </ul></dd>\n";
$html .= "  <dt>En totalité&nbsp;:</dt>\n";
$html .= "   <dd><ul>\n";
$html .= "    <li>Nombre de transaction postées sur le tdt&nbsp;: " .$allTrans . "</li>\n";
$html .= "    <li>Nombre de transaction transmises à Hélios&nbsp;: " .$allTransmitted . "</li>\n";
$html .= "    <li>Volume des transactions postées sur le tdt&nbsp;: " .$allVol. " octets</li>\n";
$html .= "    <li>Volume des transactions transmises à Hélios&nbsp;: " .$allVolTransmitted." octets</li>\n";
$html .= "   </ul></dd>\n";
$html .= " </dl>\n";
$html .= "</div>\n";
$html .= "</div>\n";


$doc->addBody($html);

$doc->buildFooter();

$doc->display();
?>