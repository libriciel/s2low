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

use S2lowLegacy\Class\RgsConnexion;

require_once(__DIR__ . "/../../init/init-www.php");

$rgsConnexion = new RgsConnexion();
if (! $rgsConnexion->isRgsConnexion()) {
    echo "KO";
    exit ;
}

echo "OK";
