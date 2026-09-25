<?php

/**
 * * @api {get} /modules/helios/api/helios_get_retour.php Récupération du contenu d'un PES_RETOUR
 * @apiDescription Récupération du fichier xml correspondant au pes_retour demandé.
 * @apiName helios_get_retour.php
 * @apiGroup Helios
 * @apiVersion 1.1.0
 *
 * @apiParam {Number} id  Identifiant des pes_retour concerné.
 * @apiSuccess String le fichier xml du pes_retour
 *
 */

use S2lowLegacy\Class\helios\PESRetourCloudStorage;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;
use S2lowLegacy\Model\HeliosRetourSQL;

list($PESRetourCloudStorage, $heliosRetourSQL) = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray(
        [PESRetourCloudStorage::class, HeliosRetourSQL::class]
    );

$retourId = Helpers :: getVarFromGet("id");

$doc = new DOMDocument();
$doc->formatOutput = true;
$doc->preserveWhiteSpace = false;
$root = $doc->createElement("get_retour");
$doc->appendChild($root);


$idElement = $doc->createElement("id", $retourId);
$resultatElement = $doc->createElement("resultat");
$messageElement = $doc->createElement("message");

$root->appendChild($idElement);
$root->appendChild($resultatElement);
$root->appendChild($messageElement);

try {
    $module = new Module();
    if (!$module->initByName("helios")) {
        $msg = "Erreur d'initialisation du module";
        throw new Exception('KO');
    }

    $me = new User();

    if (!$me->authenticate()) {
        $msg = "Échec de l'authentification";
        throw new Exception('KO');
    }

    if (!$module->isActive() || !$me->canAccess($module->get("name"))) {
        $msg = "Accès refusé";
        throw new Exception('KO');
    }

    $entity = new HeliosRetour($retourId);
    $entity->init();
    $filename = $entity->get("filename");

    if (!$filename) {
        $msg = "retour id n'est pas correcte";
        throw new Exception('KO');
    }

    $retourAuthorityId = $heliosRetourSQL->getInfo($retourId)['authority_id'];
    if (!$me->isSuper() && ($retourAuthorityId === null || !$me->canViewAuthority($retourAuthorityId))) {
        $msg = "Accès refusé";
        throw new Exception('KO');
    }

    $PESRetourCloudStorage->getPath($retourId); // Permet de récupérer le fichier s'il est dans le cloud

    if (!$entity->sendfile($filename)) {
        $msg = "Erreur d'envoi du fichier " . HELIOS_RESPONSES_ROOT . $filename . " : " . $entity->getErrorMsg();
        throw new Exception('KO');
    }
} catch (Exception $e) {
    $resultatElement->appendChild($doc->createTextNode("KO"));
    $messageElement->appendChild($doc->createTextNode($msg));
    $xmlFile = HELIOS_FILES_ROOT . "/temp/retour.xml";
    $doc->save($xmlFile);

    if (!Helpers::sendFileToBrowser($xmlFile, basename($xmlFile), "text/xml")) {
        echo "error: impossible de envoyer ce xml ";
    }
}
