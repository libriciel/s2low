<?php

// Configuration
require_once("../../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');

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

$history = ActesEnvelope::getEnvelopesHistory();

$doc = new CSVLayout();
$doc->addHeader("Date de transmission;Heure de transmission;Nom du fichier (.tar.gz.) transmis;Nom des fichiers contenus dans le fichier .tar.gz. transmis;SIREN de la collectivité émettrice;Département de la collectivité;Arrondissement de la collectivité");

if (count($history) > 0) {
    foreach ($history as $env) {
        $entry = array();
      // Récupération de la liste des fichiers pour cette enveloppe
        $files = ActesEnvelope::getEnvelopesIncludedFiles($env["id"]);
        $timestamp = Helpers::getTimestampFromBDDDate($env["date"]);

      // Date de transmission
        $entry[] = date("d-m-Y", $timestamp);
      // Heure de transmission
        $entry[] = date("H:i:s", $timestamp);
      // Nom du .tar.gz
        $entry[] = basename($env["file_path"]);
      // Nom des fichiers contenus dans l'archive
        $filenames = array();
        foreach ($files as $file) {
            $filenames[] = $file["filename"];
        }

        $entry[] = "\"" . implode("|", $filenames) . "\"";
      // SIREN de la collectivité
        $entry[] = $env["siren"];
      // Département de la collectivité
        $entry[] = "\"" . $env["department"] . "\"";
      // Arrondissement de la collectivité
        $entry[] = "\"" . $env["district"] . "\"";

        $doc->addLine($entry);
    }
}

$doc->display();
