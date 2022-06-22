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
 * \file logs_get_timestamp.php
 * \brief Page de téléchargement de l'entrée de logs accompagnée de son horodatage
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 21.08.2006
 *
 *
 * Ce script permet de télécharger une archive zip contenant l'entrée de journal
 * accompagnée de son fichier d'horodatage.
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
    $_SESSION["error"] = "Échec de l'authentification";
    header("Location: " . WEBSITE);
    exit();
}

$id = Helpers::getVarFromGet("id");

$myAuthority = new Authority($me->get("authority_id"));

$log = new Log();

if (isset($id) && ! empty($id)) {
    $log->setId($id);
    if (! $log->init()) {
        $_SESSION["error"] = "Erreur lors de l'initialisation de l'entrée de journal.";
        header("Location: " . WEBSITE_SSL . "/common/logs_view.php");
        exit();
    }
} else {
    $_SESSION["error"] = "Pas d'identifiant de log spécifié.";
    header("Location: " . WEBSITE_SSL . "/common/logs_view.php");
    exit();
}

// Vérification des permissions sur l'entrée de journal
if (! $log->canView($me)) {
    $_SESSION["error"] = "Accès refusé.";
    header("Location: " . WEBSITE_SSL . "/common/logs_view.php");
    exit();
}

if (! $log->sendArchive()) {
    $_SESSION["error"] = "Erreur de récupération de l'entrée de log et de son horodatage.<br />" . $log->getErrorMsg();
    header("Location: " . WEBSITE_SSL . "/common/logs_view.php");
}

exit();
