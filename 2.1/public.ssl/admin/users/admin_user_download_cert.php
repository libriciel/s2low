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
 * \file admin_user_download_cert.php
 * \brief Page de téléchargement du certificat utilisateur
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 20.03.2006
 * 
 *
 * Cette page permet de télécharger le certificat d'un utilisateur
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *  JS   27.07.2006  Adaptation pour Tedetis
 */


// Configuration
require_once("../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');
require_once(SITEROOT . '/class/Helpers.class.php');

$me = new User();

if (! $me->authenticate()) {
  $_SESSION["error"] = "Échec de l'authentification";
  header("Location: " . WEBSITE);
  exit();
}

if (! $me->isAdmin()) {
  $_SESSION["error"] = "Accès refusé";
  header("Location: " . WEBSITE_SSL);
  exit();
}

if (isset($_SESSION['certifpath'])) {
  $f = fopen($_SESSION['certifpath'], "rb");
  if ($f == false) {
    $_SESSION["error"] = "Impossible de télécharger le certificat";
	header("Location: " . WEBSITE_SSL);
    exit();
  }

  $content_len = (int) filesize($_SESSION['certifpath']);
  $content_file = fread($f, $content_len);
  fclose($f);
  
  $output_file = basename($_SESSION['certifpath']);
  
  @ob_end_clean();
  @ini_set('zlib.output_compression', 'Off');
  header('Pragma: public');
  
  header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
  header('Cache-Control: no-store, no-cache, must-revalidate'); // HTTP/1.1
  header('Cache-Control: pre-check=0, post-check=0, max-age=0'); // HTTP/1.1
  header('Content-Transfer-Encoding: none');
  header('Content-Type: application/octetstream; name="' . $output_file . '"'); //This should work for IE & Opera
  header('Content-Type: application/octet-stream; name="' . $output_file . '"'); //This should work for the rest
  header('Content-Disposition: attachment; filename="' . $output_file . '"');
  header("Content-length: $content_len");
  
  echo $content_file;
  exit();
} else {
  header("Location: " . WEBSITE_SSL);
  exit();
}
?>