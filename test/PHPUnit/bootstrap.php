<?php

define("TESTING_ENVIRONNEMENT",true);
define("TRACE_FILE_PATH","/tmp/s2low-phpunit.log");
define('HELIOS_FILES_UPLOAD_ROOT', "vfs://test/helios/");

set_include_path(__DIR__."/../../ext/" . PATH_SEPARATOR .   get_include_path());

require_once __DIR__."/../../docker-resources/define-from-environnement.php";


require_once(__DIR__."/../../init/init.php");


require_once(__DIR__."/S2lowTestCase.class.php");

$sqlQuery = new SQLQuery(DB_DATABASE_TEST);
$sqlQuery->setCredential(DB_USER_TEST, DB_PASSWORD_TEST);
$sqlQuery->setDatabaseHost(DB_HOST_TEST);
$sqlQuery->setClientEncoding(DB_CLIENT_ENCODING);

$psqlSchemaInfo = new PsqlSchemaInfo($sqlQuery);
$database_definition = $psqlSchemaInfo->getDatabaseDefinition();
$db_definition = file_get_contents(__DIR__."/../../db/s2low.sql.json");
$file_defintion = json_decode($db_definition,true);


$psqlDiff = new PsqlDiff();

$diff = $psqlDiff->diff($database_definition, $file_defintion);

foreach($diff as $query){
    echo $query."\n";
    try {
        $sqlQuery->query($query);
    } catch(Exception $e){};
}


