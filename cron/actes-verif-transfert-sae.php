<?php
declare(ticks = 1);

require_once( __DIR__ . "/../init/init.php");

$start = time();
echo "Debut ".date("Y-m-d H:i:s",$start)." \n";
$min_exec_time = 10;

$actesTransactionsSQL = new ActesTransactionsSQL($sqlQuery);

$date = date("Y-m-d",strtotime("-30 days"));

$allTransactions = $actesTransactionsSQL->getLastArchiveFromStatus(12,$date);

echo count($allTransactions). " transactions ACTES trouvees dans l'etat <envoye au SAE>\n";

$actesArchiveControler = $objectInstancier->get("ActesArchiveControler");
$sigtermHandler = new SigTermHandler();
foreach($allTransactions as $transactionInfo){
	$actesArchiveControler->verifArchive($transactionInfo);
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