<?php

/**
 * @api {get} /modules/helios/api/helios_get_list.php Récupération de la liste des PES_RETOUR
 * @apiDescription Obtention de la liste des PES_RETOUR avec l'état « non lu »
 * @apiName helios_get_list.php
 * @apiGroup Helios
 * @apiVersion 1.1.0
 *
 * @apiSuccess String Un fichier XML
 * @apiSuccessExample {xml} Success-Reponse:
 * <liste>
 * 		<idColl> identifiant de la collectivité </idColl>
 * 		<dateDemande> date de demande de la liste </dateDemande>
 *		<resultat> OK ou KO </resultat>
 * 		<message> message complémentaire </message>
 *		<pes_retour>
 * 			<id> identifiant du pes_retour </id>
 * 			<nom> nom </nom>
 * 			<date> date de réception du pes_retour par le tdt </date>
 * 		</pes_retour>
 * 		<pes_retour>
 * 			<id> identifiant du pes_retour </id>
 *			<nom> nom </nom>
 * 			<date> date de réception du pes_retour par le tdt </date>
 * 		</pes_retour>
 *</liste>
 *
 *
 */

require_once (__DIR__."/../../../../init/init.php");
$frontController->go("Helios","getPESRetourList");
