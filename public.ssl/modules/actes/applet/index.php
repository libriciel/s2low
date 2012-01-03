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
 * \file public.ssl/modules/actes/applet/index.php
 * \brief Page de signature des fichiers avec applet Java
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 30.08.2006
 * 
 *
 * Cette page utilise une applet java permettant de générer
 * les signatures des fichiers sélectionnés par l'utilisateur
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */
// Récupération noms de fichier

$files = (isset($_POST["files"])) ? $_POST["files"] : null;

if (! $files || ! is_array($files) || count($files) <= 0) {
  echo "Pas de fichier à signer. Abandon.";
  exit();
}

echo "<?xml version=\"1.0\" encoding=\"iso-8859-15\"?>\n";

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr">
 <head>
  <title>Applet de signature</title>
  <meta http-equiv="content-type" content="text/html; charset=iso-8859-15" />
  <link rel="stylesheet" type="text/css" href="/custom/styles/style.css" />
 </head>
 <body>
<div id="content" style="margin-left: 0px">
 <h2>Génération de signatures de fichiers</h2>
 <p>Cette page va procéder à la génération des fichiers de signature des fichiers contenus dans la liste ci-dessous.<br /><br />
Procédure de création des fichiers de signatures&nbsp;</p>
<ul>
<li>Choisissez le certificat qui sera utilisé pour signer (utilisez le bouton «&nbsp;Choisir un certificat&nbsp;»)&nbsp;;</li>
<li>Saisissez le mot passe de ce certificat dans le champ «&nbsp;Mot de passe certificat&nbsp;» (laissez vide si le certificat n'a pas de mot de passe)&nbsp;;</li>
<li>Lancer la génération des signatures en utilisant le bouton «&nbsp;Signer&nbsp;».</li>
</ul>
<p>
Les fichiers de signature seront générés au même emplacement que les fichiers originaux et porteront le même nom avec l'ajout d'une extension «&nbsp;.sig&nbsp;».<br />
Une fois l'opération completée avec succès, fermez cette fenêtre puis ajoutez les fichiers de signature (.sig) dans le formulaire de création de transaction.
</p>
 <!--[if !IE]> Firefox and others will use outer object -->
 <object classid="java:signature.AppletSignature" type="application/x-java-applet;version=1.5" archive="applet.jar, bcmail-jdk15-133.jar, bcprov-jdk15-133.jar" height="280" width="770">
 <!-- Konqueror browser needs the following param -->
 <param name="archive" value="applet.jar, bcmail-jdk15-133.jar, bcprov-jdk15-133.jar" />
 <PARAM NAME = CODEBASE VALUE = "./" >
<?php

$i = 1;
if (is_array($files) && count($files) > 0) {
  foreach ($files as $file) {
	echo "<param name=\"fichier" . $i . "\" value=\"" . $file . "\" />\n";
	$i++;
  }
}

?>
 <!--<![endif]-->
 <!-- MSIE (Microsoft Internet Explorer) will use inner object -->
 <object classid="clsid:8AD9C840-044E-11D1-B3E9-00805F499D93" codebase="http://java.sun.com/update/1.5.0/jinstall-1_5_0-windows-i586.cab" height="280" width="770">
 <param name="code" value="signature.AppletSignature" />
 <PARAM NAME = CODEBASE VALUE = "./" >
 <param name="archive" value="applet.jar,bcmail-jdk15-133.jar,bcprov-jdk15-133.jar" />
<?php

$i = 1;
if (is_array($files) && count($files) > 0) {
  foreach ($files as $file) {
	echo "<param name=\"fichier" . $i . "\" value=\"" . $file . "\" />\n";
	$i++;
  }
}
?>
 <strong>
 Votre navigateur ne comporte pas le support de Java.<br />
 <a href="http://java.sun.com/javase/downloads/index.jsp">Obtenir le dernier plugin Java.</a>
 </strong>
 </object>
 <!--[if !IE]>--> 
 </object>
 <!--<![endif]-->
 <p><a href="http://java.sun.com/javase/downloads/index.jsp">Plugin Java en version 1.6</a> minimum requis pour le fonctionnement correct de cette page.</p>
 </div>
 </body>
</html>
