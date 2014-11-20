<?php


require_once( __DIR__."/../../init/init.php");


$heliosTransactionsSQL = new HeliosTransactionsSQL($sqlQuery);
$heliosTransactionsSQL->updateLastStatusId();