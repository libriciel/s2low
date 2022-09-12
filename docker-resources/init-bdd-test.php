<?php

use S2lowLegacy\Controller\PostgreSQLController;
use S2lowLegacy\Lib\ObjectInstancier;
use S2lowLegacy\Lib\SQLQuery;

require_once __DIR__ . "/../init/init.php";
\S2lowLegacy\Class\LegacyObjectsManager::setLegacyObjectInstancier();

$objectInstancierTest = new ObjectInstancier();
$sqlQueryTest = new SQLQuery(DB_DATABASE_TEST);
$sqlQueryTest->setCredential(DB_USER_TEST, DB_PASSWORD_TEST);
$sqlQueryTest->setDatabaseHost(DB_HOST_TEST);
$objectInstancierTest->set(SQLQuery::class, $sqlQueryTest);
$objectInstancierTest->set('database_json_definition_filepath', __DIR__ . "/../db/s2low.sql.json");
$objectInstancierTest->set('database_sql_definition_filepath', __DIR__ . "/../db/s2low.sql");

/** @var PostgreSQLController $postgreSQLController */
$postgreSQLController = $objectInstancierTest->get(PostgreSQLController::class);
$postgreSQLController->alterDatabase(function ($m) {
    echo "[Mise a  jour base de donnees de test]" . $m . "\n";
});
