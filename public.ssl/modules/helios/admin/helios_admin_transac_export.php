<?php

/**
 * \file helios_admin_transac_export.php
 * \brief Page d'export de la liste des transactions au format CSV
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 10.08.2006
 *
 *
 * Cette page effectue l'extraction de l'ensemble des transactions
 * envoyées et génère un fichier CSV.
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

// Configuration
use S2lowLegacy\Class\CSVLayout;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;

// Instanciation du module courant
$moduleSQL = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2lowLegacy\Model\ModuleSQL::class);
        $module = $moduleSQL->initByName('helios');
if (!$module) {
    $_SESSION['error'] = "Erreur d'initialisation du module";
    header('Location: ' . WEBSITE_SSL);
    exit();
}

$me = new User();

if (! $me->authenticate()) {
    $_SESSION['error'] = "Échec de l'authentification";
    header('Location: ' . Helpers::getLink('connexion-status'));
    exit();
}

if (! $me->isSuper()) {
    $_SESSION['error'] = 'Accès refusé';
    header('Location: ' . WEBSITE_SSL);
    exit();
}

if (! $module->isActive() || ! $me->canAccess($module->get('name'))) {
    $_SESSION['error'] = 'Accès refusé';
    header('Location: ' . WEBSITE_SSL);
    exit();
}

$history = HeliosTransaction::getTransationHistory();


$doc = new CSVLayout();
$doc->addHeader('Date de transmission;Nom du fichier transmis;Empreinte SHA1;Taille du fichier (octets);SIREN de la collectivité émettrice;Département de la collectivité;Arrondissement de la collectivité');

if (count($history) > 0) {
    foreach ($history as $env) {
        $entry = [];
      // Récupération de la liste des fichiers pour cette enveloppe
        $file = $env['filename'];
        $timestamp = Helpers::getTimestampFromBDDDate($env['date']);

      // Date de transmission
        $entry[] = date('c', $timestamp);
      // Nom du fichier
        $entry[] = $file;
      //sha1
        $entry[] = $env['sha1'];
      //taille fichier
        $entry[] = $env['file_size'];
      // SIREN de la collectivité
        $entry[] = $env['siren'];
      // Département de la collectivité
        $entry[] = "\"" . $env['department'] . "\"";
      // Arrondissement de la collectivité
        $entry[] = "\"" . $env['district'] . "\"";

        $doc->addLine($entry);
    }
}

$doc->display();
