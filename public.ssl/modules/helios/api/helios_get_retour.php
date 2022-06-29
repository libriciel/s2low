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

require_once("../../../../init/init.php");

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
