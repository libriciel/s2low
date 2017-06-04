<?php


require_once( __DIR__ . "/../../init/init.php");

$sqlQuery->query("SET client_encoding='UTF8'; ");
print_r($sqlQuery->query("SELECT flux_retour FROM actes_transactions_workflow WHERE transaction_id=? AND status_id=?",14,4));