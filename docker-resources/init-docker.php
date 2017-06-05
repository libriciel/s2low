<?php

require_once __DIR__."/../init/init.php";
require_once __DIR__."/S2lowBootstrap.class.php";


/** @var ObjectInstancier $objectInstancier */
/** @var S2lowBootstrap $slowBootrap */
$slowBootrap = $objectInstancier->get('S2lowBootstrap');


$slowBootrap->bootstrap();


if (DB_DATABASE_TEST) {

	$objectInstancierTest = new ObjectInstancier();
	$sqlQueryTest = new SQLQuery(DB_DATABASE_TEST);
	$sqlQueryTest->setCredential(DB_USER_TEST, DB_PASSWORD_TEST);
	$sqlQueryTest->setDatabaseHost(DB_HOST_TEST);
	$sqlQueryTest->setClientEncoding(DB_CLIENT_ENCODING);
	$objectInstancierTest->set('SQLQuery',$sqlQueryTest);
	$objectInstancierTest->set('database_json_definition_filepath',__DIR__."/../db/s2low.sql.json");
	$objectInstancierTest->set('database_sql_definition_filepath',__DIR__."/../db/s2low.sql");

	/** @var PostgreSQLController $postgreSQLController */
	$postgreSQLController = $objectInstancierTest->get('PostgreSQLController');
	$postgreSQLController->alterDatabase(function($m){echo "[Mise à jour base de données de test]".$m."\n";});
}

