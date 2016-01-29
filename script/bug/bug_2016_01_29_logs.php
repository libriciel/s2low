<?php

/**
 * La partie Java n'enregistait pas correctemnt l'authority_id de la table journal
 * (il s'agit d'une dénormalisation de cette table pour des soucis de performances)
 *
 * Cela crée un bug lors de l'affichage des journaux des collectivités (la requête utilisant les colonnes dénormalisé)
 * Ce script est à passé avant le passage à la version 2.4 de S2LOW
 *
 */


require_once (__DIR__."/../../config/config.php");
require_once (SITEROOT . '/init/init.php');


$sql = "UPDATE logs SET authority_id=users.authority_id FROM users " .
		" WHERE logs.user_id=users.id AND logs.authority_id IS NULL AND logs.user_id IS NOT NULL";

$sqlQuery->query($sql);

echo "ok\n";