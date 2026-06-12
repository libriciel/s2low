<?php

// Configuration
use S2low\Services\CloudFileStorageInterface;
use S2low\Services\LocalFileResolver;
use S2lowLegacy\Class\helios\PESRetourCloudStorage;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\HeliosRetourSQL;

/** @var CloudFileStorageInterface $storePesRetourService */
/** @var LocalFileResolver $pesAllerPathResolver */
list($heliosRetourSQL,$authoritySQL,$storePesRetourService, $pesAllerPathResolver) = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray(
        [HeliosRetourSQL::class, AuthoritySQL::class, 'app.store.file.pes_retour', 'app.localFileResolver.pes_retour']
    );

// Instanciation du module courant
$moduleSQL = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(\S2lowLegacy\Model\ModuleSQL::class);
        $module = $moduleSQL->initByName("helios");
if (!$module) {
    $_SESSION["error"] = "Erreur d'initialisation du module";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$me = new User();

if (!$me->authenticate()) {
    $_SESSION["error"] = "Échec de l'authentification";
    header("Location: " . Helpers::getLink("connexion-status"));
    exit();
}

if (!$module->isActive() || !$me->canAccess($module->get("name"))) {
    $_SESSION["error"] = "Accès refusé";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$retourId = Helpers :: getVarFromGet("id");

// Vérification des permissions
$info = $heliosRetourSQL->getInfo($retourId);

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
    $storePesRetourService->downloadFileFromCloud($retourId);
    $filepath = $pesAllerPathResolver->getFullPath($retourId);
} catch (Exception $e) {
    $_SESSION["error"] = "Erreur lors de la r?cup?ration du fichier : " . $e->getMessage();
    header("Location: " . Helpers::getLink("/modules/helios/helios_retour.php"));
    exit();
}

Helpers::sendFileToBrowser($filepath, $filename, "text/xml");
