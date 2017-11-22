<?php
declare(ticks = 1);

require_once( __DIR__ . "/../init/init.php");

$start = time();
echo "Debut ".date("Y-m-d H:i:s",$start)." \n";
$min_exec_time = 10;

$heliosTransactionsSQL = new HeliosTransactionsSQL($sqlQuery);
$allTransactions = $heliosTransactionsSQL->getArchiveFromStatusWithSAE(9);

echo count($allTransactions). " transactions HELIOS trouvees dans l'etat <envoye au SAE>\n";

/** @var HeliosArchiveControler $heliosArchiveControler */
$heliosArchiveControler = $objectInstancier->get("HeliosArchiveControler");
$sigtermHandler = new SigTermHandler();
foreach($allTransactions as $transactionInfo){
	$heliosArchiveControler->verifArchive($transactionInfo);
    if ($sigtermHandler->isSigtermCalled()){
        break;
    }
}

$stop = time();
echo "Fin ".date("Y-m-d H:i:s",$stop)." \n";
$sleep = $min_exec_time - ($stop -$start);
if ($sleep > 0){
    echo "Arret du script : $sleep \n";
    sleep($sleep);
}