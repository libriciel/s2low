<?php

/**
 * \file helios_admin_transac_retour_export.php
 * \brief Page d'export de la liste des transactions pes_retour au format CSV
 *
 *
 * Cette page effectue l'extraction de l'ensemble des transactions
 * retournées et génère un fichier CSV.
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

// Instanciation du module courant
use S2lowLegacy\Class\CSVLayout;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;

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

$history = HeliosRetour::getRetourHistory();


$doc = new CSVLayout();
$doc->addHeader('Date de reception;Nom du fichier transmis;SIREN de la collectivite destinataire;Empreinte sha1; Taille du fichier');

if (count($history) > 0) {
    foreach ($history as $env) {
        $entry = [];
        $timestamp = Helpers::getTimestampFromBDDDate($env['date']);

      // Date de transmission
        $entry[] = date('c', $timestamp);
      // Nom du fichier
        $entry[] = $env['filename'];
      // SIREN de la collectivité
        $entry[] = $env['siren'];

        $entry [] = $env['sha1'];
        $entry [] = $env['file_size'];
        $doc->addLine($entry);
    }
}

$doc->display();
