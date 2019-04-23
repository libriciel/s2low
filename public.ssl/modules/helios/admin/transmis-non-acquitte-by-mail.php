<?php



require_once( __DIR__ . "/../../../../init/init-www-helios.php");

if ($userInfo['role'] != 'SADM'){
    $_SESSION["error"] = "Super admin only !";
    header("Location: " . WEBSITE);
    exit();
}


/** @var HeliosTransactionsSQL $heliosTransactionsSQL */
$heliosTransactionsSQL = $objectInstancier->{'HeliosTransactionsSQL'};
$transactions_list = $heliosTransactionsSQL->getNonAcquitte();

ob_start();
if (! $transactions_list){
    $subject =  "Aucune transaction n est reste en transmis";

} else {
    $subject = count($transactions_list) . " transactions sont reste a l'etat transmis.";
}

$output = fopen("php://output","w");

foreach($transactions_list as $line){
    unset($line['id']);
    unset($line['filename']);
    fputcsv($output, $line);

}
fclose($output);

$content = ob_get_contents();
ob_end_clean();

mail($userInfo['email'],$subject,$content);

$_SESSION['error'] = "Mail envoye a {$userInfo['email']}";
header("Location: transmis-non-acquitte.php");
