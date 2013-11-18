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
#!/usr/bin/php
<?php

// Configuration
require_once("../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');

$depFile = "./depts2006.txt";

$fh = fopen($depFile, 'r') or die ("Impossible d'ouvrir le fichier des départements\n");

$db =DatabasePool::getInstance();

if (! $db->begin()) {
  echo "Erreur lors de l'initialisation de la transaction.\n";
  exit();
}

if (! $db->exec("DELETE FROM authority_departments")) {
  echo "Erreur lors de la purge de la table.\n";
  $db->rollback();
  exit();
}

while ($row = fgets($fh)) {
  $matches = array();
  if (preg_match("/^[0-9]+\s+([0-9a-zA-Z]+)\s+[a-zA-Z0-9]+\s+[0-9]+\s+[-A-Z\s']+\s+([A-Z].+[a-z])\s*$/", $row, $matches)) {
	$code = $matches[1];

	while (strlen($code) < 3) {
	  $code = '0' . $code;
	}

	$name = $matches[2];

	$sql = "INSERT INTO authority_departments (code, name) VALUES ('" . addslashes($code) . "', '" . addslashes($name) . "')";

    if (! $db->exec($sql)) {
	  echo "Erreur lors de l'insertion dans la base.\n";
	  $db->rollback();
	  exit();
    }
  }
}

if (! $db->commit()) {
  echo "Erreur lors de la validation de la transaction.\n";
  $db->rollback();
  exit();
}
?>