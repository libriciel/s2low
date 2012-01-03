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
 * \file public.ssl/modules/actes/actes_batch_add.php
 * \brief Page de création d'une transaction par lots
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 08.02.2007
 * 
 *
 * Cette page permet la création d'un lot
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

$doc = new HTMLLayout();

$doc->setTitle("Tedetis : Traitement par lots module actes");

$doc->buildMenu($me);

$js = "<script type=\"text/javascript\">\n";
$js .= "  function redirect_to_batch(batch_id) {\n";
$js .= "    alert('Redirect to batch');\n";
$js .= "    document.location = '" . WEBSITE_SSL . "/modules/actes/actes_batch_show.php?id=' + batch_id;\n";
$js .= "  }\n";
$js .= "</script>\n";

$doc->addHeader($js);

$html = "<div id=\"content\">\n";
$html .= "<h1>ACTES - Création d'un lot</h1>\n";
 
$html .= " <object classid=\"java:selection.AppletMultipleSelection\" type=\"application/x-java-applet;version=1.5\" archive=\" applet/AppletMultipleSelection.jar, applet/commons-httpclient-3.0.1.jar, applet/bcmail-jdk15-133.jar, applet/commons-logging-api.jar, applet/commons-codec-1.3.jar, applet/bcprov-jdk15-133.jar\" height=\"400\" width=\"750\">\n";
$html .= " <!-- Konqueror browser needs the following param -->\n";
$html .= ' <param name="charset" value="Cp1252">';
$html .= " <param name=\"archive\" value=\"applet/AppletMultipleSelection.jar, applet/commons-httpclient-3.0.1.jar, applet/bcmail-jdk15-133.jar, applet/commons-logging-api.jar, applet/commons-codec-1.3.jar, applet/bcprov-jdk15-133.jar\" />\n";
$html .= " <param name=\"max_size\" value=\"" . ACTES_MAX_BATCH_UPLOAD_SIZE . "\" />\n";
$html .= " <param name=\"url\" value=\"" . WEBSITE_SSL . "/modules/actes/actes_batch_create.php\" />\n";
$html .= " <param name=\"url_redirect\" value=\"" . WEBSITE_SSL . "/modules/actes/actes_batch_show.php?id=\" />\n";
$html .= " <param name=\"mayscript\" value=\"true\"/>\n";
$html .= " <!--<![endif]-->\n";
$html .= " <!-- MSIE (Microsoft Internet Explorer) will use inner object -->\n";
$html .= " <object classid=\"clsid:8AD9C840-044E-11D1-B3E9-00805F499D93\" codebase=\"http://java.sun.com/update/1.5.0/jinstall-1_5_0-windows-i586.cab\" height=\"400\" width=\"750\">\n";
$html .= ' <param name="charset" value="Cp1252">';
$html .= " <param name=\"code\" value=\"selection.AppletMultipleSelection\" />\n";
$html .= " <param name=\"archive\" value=\"applet/AppletMultipleSelection.jar, applet/commons-httpclient-3.0.1.jar, applet/bcmail-jdk15-133.jar, applet/commons-logging-api.jar, applet/commons-codec-1.3.jar, applet/bcprov-jdk15-133.jar\" />\n";
$html .= " <param name=\"max_size\" value=\"" . ACTES_MAX_BATCH_UPLOAD_SIZE . "\" />\n";
$html .= " <param name=\"url\" value=\"" . WEBSITE_SSL . "/modules/actes/actes_batch_create.php\" />\n";
$html .= " <param name=\"url_redirect\" value=\"" . WEBSITE_SSL . "/modules/actes/actes_batch_show.php?id=\" />\n";
$html .= " <param name=\"mayscript\" value=\"true\"/>\n";
$html .= " <strong>\n";
$html .= " Votre navigateur ne comporte pas le support de Java.<br />\n";
$html .= " <a href=\"http://java.sun.com/javase/downloads/index.jsp\">Obtenir le dernier plugin Java.</a>\n";
$html .= " </strong>\n";
$html .= " </object>\n";
$html .= " <!--[if !IE]>-->\n";
$html .= " </object>\n";
$html .= " <!--<![endif]-->\n";
$html .= " <p><a href=\"http://java.sun.com/javase/downloads/index.jsp\">Plugin Java en version 1.6</a> minimum requis pour le fonctionnement correct de cette page.</p>\n";
$html .= "</div>\n";

$doc->addBody($html);

$doc->buildFooter();

$doc->display();
?>