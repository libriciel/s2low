#! /usr/bin/php
<?php

require_once __DIR__."/../init/init.php";

require_once SITEROOT."/public.ssl/modules/actes/class/ActesEnvelope.class.php";
require_once SITEROOT."/public.ssl/modules/actes/class/ActesTransaction.class.php";

$queue = new \Pheanstalk\Pheanstalk("beanstalkd");
$queue->watch("actes-antivirus");

$transactionSQL = new ActesTransactionsSQL($sqlQuery);

//TODO et le sigterm en plein milieu ?

while($job = $queue->reserve()){
	try {

	    ##TODO check antivirus
        $transaction_id = $job->getData();

        echo "Traitement transaction $transaction_id : ";
        $zeTrans = new ActesTransaction();
        $zeTrans->setId($transaction_id);
        $zeTrans->init();

        if ($zeTrans->get('antivirus_check')){
            echo "Transaction is already virus free !";
            $queue->delete($job);
            continue;
        }

        $zeEnv = new ActesEnvelope($zeTrans->get("envelope_id"));
        $zeEnv->init();

        $archive_path =  ACTES_FILES_UPLOAD_ROOT."/". $zeEnv->get('file_path');

		if ($zeEnv->checkArchiveSanity($archive_path)) {
			$transactionSQL->setAntivirusCheck($transaction_id);
			echo "OK\n";
		} else {
			$message = $zeEnv->getErrorMsg();
			echo "Virus Found : $message\n";
			$transactionSQL->updateStatus($transaction_id, -1, $message);
		}
		$queue->delete($job);
	} catch (Exception $e){
        echo "Probleme during antivirus check : ".$e->getMessage();
        $queue->release($job,\Pheanstalk\PheanstalkInterface::DEFAULT_PRIORITY,60);
    }
}


/*
mode nominal : inscription dans la file, file ok, traitement du job, job supprimé
 Beanstalkd marche plus :
    - pas d'inscription dans la file ! > passage d'un script qui recrée la file

    - inscription dans la file, reboot de beanstalkd, perte des jobs > passage d'un script qui recrée la file

    - clamav arreté > job burried > script qui relance les jobs burried

Comment traiter les job burried ?
Comment traiter les job quand beanstalkd est down ?


*/