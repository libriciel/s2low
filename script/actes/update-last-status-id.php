<?php

require_once(__DIR__ . "/../../init/init.php");

$actesTransactionsSQL = new ActesTransactionsSQL($sqlQuery);
$actesTransactionsSQL->updateLastStatusId();
