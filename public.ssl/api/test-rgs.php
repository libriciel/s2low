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

/** @var Initialisation $init */
$init = LegacyObjectsManager::getLegacyObjectInstancier()->get(Initialisation::class);
$init->init();

$rgsConnexion = new RgsConnexion();
if (! $rgsConnexion->isRgsConnexion()) {
    echo "KO";
    exit ;
}

echo "OK";
