<?php

// Configuration
use S2lowLegacy\Class\helios\PESRetourCloudStorage;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;
use S2lowLegacy\Model\AuthoritySQL;
use S2lowLegacy\Model\HeliosRetourSQL;

list($heliosRetourSQL,$authoritySQL,$pesRetourCloudStorage) = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray(
        [HeliosRetourSQL::class, AuthoritySQL::class, PESRetourCloudStorage::class]
    );

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
    header("Location: " . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("connexion-status"));
    exit();
}

if (!$module->isActive() || !$me->canAccess($module->get("name"))) {
    $_SESSION["error"] = "Accès refusé";
    header("Location: " . WEBSITE_SSL);
    exit();
}

$retourId = \S2lowLegacy\Class\Helpers\RequestHelper::getVarFromGet("id");

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
    $filepath = $pesRetourCloudStorage->getPath($retourId);
} catch (Exception $e) {
    $_SESSION["error"] = "Erreur lors de la r?cup?ration du fichier : " . $e->getMessage();
    header("Location: " . \S2lowLegacy\Class\Helpers\UrlHelper::getLink("/modules/helios/helios_retour.php"));
    exit();
}

\S2lowLegacy\Class\Helpers\ResponseHelper::sendFileToBrowser($filepath, $filename, "text/xml");
