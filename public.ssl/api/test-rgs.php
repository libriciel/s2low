<?php

/**
 * * @api {get} /api/test-rgs.php Test RGS
 * @apiDescription Indique si le certificat de connexion est reconnu comme &eacute;tant RGS
 * @apiName test-rgs.php
 * @apiGroup Connexion
 * @apiVersion 2.4.0
 *
 * @apiSuccess {String} OK
 * @apiError (Error) {String} KO
 *
 */

use S2lowLegacy\Class\Initialisation;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\RgsConnexion;

LegacyObjectsManager::getLegacyObjectInstancier()->get(Initialisation::class)->doInit();

$rgsConnexion = new RgsConnexion();

$message = 'OK';
if (! $rgsConnexion->isRgsConnexion()) {
    $message = 'KO';
}

echo $message;
