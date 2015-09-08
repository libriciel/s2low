<?php 

require_once(dirname(__FILE__)."/../../../../config/config.php");

require_once(dirname(__FILE__)."/../../../../class/SQLQuery.class.php");
$sqlQuery = new SQLQuery(DB_DATABASE);
$sqlQuery->setDatabaseHost(DB_HOST);
$sqlQuery->setCredential(DB_USER,DB_PASSWORD);


$sqlQuery->query("SELECT count(*) FROM authorities");
$sqlQuery->query("SELECT count(*) FROM users");

