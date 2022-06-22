<?php

// Configuration
require_once("../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');
require_once(SITEROOT . '/public.ssl/modules/helios/class/HeliosRetour.class.php');

// Instanciation du module courant
$module = new Module();
if (!$module->initByName("helios")) {
    $_SESSION["error"] = "Erreur d'initialisation du module";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$me = new User();

if (!$me->authenticate()) {
    $_SESSION["error"] = "Échec de l'authentification";
    header("Location: " . WEBSITE);
    exit();
}

if (!$module->isActive() || !$me->canAccess($module->get("name"))) {
    $_SESSION["error"] = "Accès refusé";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$retourId = Helpers :: getVarFromGet("id");

// Vérification des permissions
$heliosRetourSQL = new HeliosRetourSQL($sqlQuery);
$info = $heliosRetourSQL->getInfo($retourId);

$authoritySQL = new AuthoritySQL($sqlQuery);
$authtority_info = $authoritySQL->getInfo($info['authority_id']);

if (! $me->isSuper()) {
    if ($me->get("authority_id") != $info['authority_id']) {
        if (! ($me->isGroupAdmin() && $me->get("authority_group_id") == $authtority_info['authority_group_id'])) {
            echo "KO\nAccès refusé";
            exit();
        }
    }
}


$entity = new HeliosRetour($retourId);
$entity->init();


$filename = $entity->get("filename");

try {
    $pesRetourCloudStorage = $objectInstancier->get(CloudStorageFactory::class)->getInstanceByClassName(PESRetourCloudStorage::class);
    $filepath = $pesRetourCloudStorage->getPath($retourId);
} catch (Exception $e) {
    $_SESSION["error"] = "Erreur lors de la r?cup?ration du fichier : " . $e->getMessage();
    header("Location: " . WEBSITE_SSL . "/modules/helios/helios_retour.php");
    exit();
}

Helpers::sendFileToBrowser($filepath, $filename, "text/xml");
