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

// Configuration
require_once("../../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');
require_once("../class/HeliosRetour.class.php");
// Instanciation du module courant
$module = new Module();
if (! $module->initByName("helios")) {
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

if (! $me->isSuper()) {
    $_SESSION["error"] = "Accès refusé";
    header("Location: " . WEBSITE_SSL);
    exit();
}

if (! $module->isActive() || ! $me->canAccess($module->get("name"))) {
    $_SESSION["error"] = "Accès refusé";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$history = HeliosRetour::getRetourHistory();


$doc = new CSVLayout();
$doc->addHeader("Date de reception;Heure de recetpion;Nom du fichier transmis;SIREN de la collectivite destinataire;Empreinte sha1");

if (count($history) > 0) {
    foreach ($history as $env) {
        $entry = array();
        $timestamp = Helpers::getTimestampFromBDDDate($env["date"]);

      // Date de transmission
        $entry[] = date("d-m-Y", $timestamp);
      // Heure de transmission
        $entry[] = date("H:i:s", $timestamp);
      // Nom du fichier
        $entry[] = $env["filename"];
      // SIREN de la collectivité
        $entry[] = $env["siren"];

      //$entry [] =$env["sha1"];
        $doc->addLine($entry);
    }
}

$doc->display();
