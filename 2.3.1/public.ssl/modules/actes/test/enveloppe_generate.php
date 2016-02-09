<?php 

require_once(dirname(__FILE__)."/../../../../init/init-www-actes.php");

require_once(SITEROOT."/public.ssl/modules/actes/class/ActesTransactionXML.class.php");
require_once(SITEROOT."/public.ssl/modules/actes/class/ActesClassification.class.php");
require_once(SITEROOT."/public.ssl/modules/actes/class/ActesEnvelopeSerialSQL.class.php");

$actesTransactionsXML = new ActesTransactionsXML();

$transactionInfo["date_decision"] =  date("Y-m-d");
$transactionInfo['numero_interne'] = strtoupper(uniqid());
$transactionInfo['code_nature']  = 1;
$transactionInfo['classification']  = array(1,1);
$transactionInfo['objet'] = "Enveloppe de test #{$transactionInfo['numero_interne']}";
$transactionInfo['classification_date_version'] =  ActesClassification :: getLastRevisionDate($userInfo['authority_id']);
$transactionInfo["type"] = 1 ;

$transactionInfo["nom_fichier"] = $actesTransactionsXML->getNewFileName($authorityInfo,$transactionInfo,1,"pdf");
$transactionInfo["annexe"] = array();

$transaction_file_name = $actesTransactionsXML->getTransactionFileName($authorityInfo,$transactionInfo);

$transactionXML = $actesTransactionsXML->getXML($transactionInfo);

$myAuthority = new Authority($userInfo['authority_id']);

$actesEnveloppeXML = new ActesEnveloppeXML();

$actesEnvelopeSerial = new ActesEnvelopeSerialSQL(DatabasePool::getInstance());
$serialNumber = $actesEnvelopeSerial->getNext($userInfo['authority_id']);

$enveloppe_file_name = $actesEnveloppeXML->getName(ACTES_APPLI_NAME,$authorityInfo['siren'],$serialNumber);
$enveloppeXML = $actesEnveloppeXML->getEnveloppe($authorityInfo,$userInfo,array($transaction_file_name));

$archive = new ActesArchive(TEDETIS_TMP_PATH);
$archive->addContent($enveloppe_file_name,$enveloppeXML);
$archive->addContent($transaction_file_name,$transactionXML);
$archive->addFile($transactionInfo["nom_fichier"],SITEROOT."/data-exemple/vide.pdf");

$archive_name = $archive->getFileName($enveloppe_file_name);
$archive_path = $archive->generate($enveloppe_file_name);



header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename='.$archive_name);
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . filesize($archive_path));
readfile($archive_path);
   
