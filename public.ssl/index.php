<?php
/*
 * TéDéTIS - Copyright 2006 Alternance-Soft
 * Contributeur : Jérôme Schell, Août 2006 
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
 * \file public.ssl/index.php
 * \brief Page d'entrée du site Tedetis
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 17.07.2006
 * 
 *
 * Page d'accueil du site TéDéTis (authentifié).
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

// Configuration
require_once("../config/config.php");
require_once(SITEROOT . '/class/include.class.php');

$me = new User();

if (! $me->authenticate()) {
  $_SESSION["error"] = "Échec de l'authentification";
  header("Location: " . WEBSITE);
  exit();
}

$doc = new HTMLLayout();

$myAuthority = new Authority($me->get("authority_id"));

$doc->setTitle(WEBSITE_TITLE);

$doc->buildMenu($me);

$html = "<div id=\"content\">";
$html .= " <h1>Espace de télétransmission</h1>\n";
$html .= "<p>Vous êtes connecté avec le rôle";

if ($me->isSuper()) {
  $html .= " de super administrateur";
} elseif ($me->isGroupAdmin()) {
  $myGroup = new Group($me->get("authority_group_id"));
  $html .= " d'administrateur du groupe " . $myGroup->get("name");
} elseif ($me->isAdmin()) {
  $html .= " d'administrateur de la collectivité " . $myAuthority->get("name");
} else {
  $html .= " d'utilisateur de la collectivité " . $myAuthority->get("name");
}

$html .= ".<br />\n";

$html .= "Le menu de gauche vous donne accès aux opérations permises par ce rôle.<br /><br />\n";
$html .= "Le «&nbsp;Journal des événements&nbsp;» consigne l'ensemble des événements relatifs à vos opérations sur le site.<br /><br />";

if (defined("HOTLINE_NUM")) {
  $html .= "La hotline de support est disponible pour toute question au " . HOTLINE_NUM . ".<br /><br />\n";
}

$html .= "Merci de signaler tout problème rencontré sur la plate-forme ";

if (defined("SUPPORT_URL")) {
  $html .= " sur le <a href=\"" . SUPPORT_URL . "\">site support</a> réservé à cet effet";
} else {
  $html .= " au <a href=\"mailto:" . WEBMASTER . "\">webmaster</a>";
}

$html .= ".<br />\n";

$html .= "</p>\n";
$html .= "</div>\n";

$doc->addBody($html);

$doc->buildFooter();

$doc->display();
//test
?>
