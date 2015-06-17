<?php
require_once( __DIR__ . "/../../../init/init-www-dia.php");

$recuperateur = new Recuperateur($_GET);
$id = $recuperateur->getInt('id',1);

$transactionDIA = new TransactionDIA($sqlQuery);
$dia_info = $transactionDIA->getInfo($id);

if (!$dia_info){
	Helpers :: returnAndExit(1, "Cette DIA n'existe pas", WEBSITE_SSL."/modules/dia/" );
}

header('Content-Type: text/xml');
header('Content-disposition: filename="message.xml"');

echo $dia_info['message_xml'];
