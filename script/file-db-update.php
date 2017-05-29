<?php
require_once( __DIR__."/../init/init.php");


//TODO penser aussi que dans s2low, on a aussi des données à sauvegarder

$psqlSchemaInfo = new PsqlSchemaInfo($sqlQuery);

$databaseDefinition = $psqlSchemaInfo->getDatabaseDefinition();

file_put_contents(__DIR__."/../db/s2low.sql.json",json_encode($databaseDefinition));


$sqlDiff = new PsqlDiff();
$command_list = $sqlDiff->diff(array(), $databaseDefinition);

$sql_content = implode("\n",$command_list) . "\n";

file_put_contents(__DIR__."/../db/s2low.sql",$sql_content);
