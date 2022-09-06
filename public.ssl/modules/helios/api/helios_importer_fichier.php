<?php

/**
 * * @api {post} /modules/helios/api/helios_importer_fichier.php Postage d'un fichier XML
 * @apiDescription Soumission d'un fichier XML représentant le document à transmettre à un comptable. Nécessite
 *      la fourniture d'un fichier .xml. La plateforme se charge de valider le fichier et de la poster au destinataire.
 *      Attention : une signature invalide donnera lieu à un OK pour notifier la création de la transaction mais cette
 *      dernière apparaitra immédiatement en statut erreur (-1).
 * @apiName helios_importer_fichier.php
 * @apiGroup Helios
 * @apiVersion 1.1.0
 *
 * @apiParam {File} enveloppe  Fichier XML qui représente le document financière
 * @apiParam {File} [signature] Fichier PKC#7 contenant la signature
 * @apiSuccess String Un fichier XML
 * @apiSuccessExample {xml} Success-Reponse:
 *  <import>
 *      <id> numéro de la transaction créée</id>
 *      <resultat> OK ou KO </resultat>
 *      <message> message complémentaire </message>
 *  </import>
 */

use S2lowLegacy\Lib\FrontController;

$frontController = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(FrontController::class);
$frontController->go("Helios", "importAPI");
