<?php
require_once( __DIR__."/../init/init.php");


$psqlSchemaInfo = new PsqlSchemaInfo($sqlQuery);
$database_definition = $psqlSchemaInfo->getDatabaseDefinition();

$db_definition = file_get_contents(__DIR__."/../db/s2low.sql.json");
$file_defintion = json_decode($db_definition,true);


$psqlDiff = new PsqlDiff();
$diff = $psqlDiff->diff($database_definition, $file_defintion);
echo implode("\n", $diff);
echo "\n";


