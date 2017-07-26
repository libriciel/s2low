<?php
exit;
/*
 * L'application a générerer des fichiers XML sans objets...
 * 
 */

require_once ( __DIR__."/../../init/init.php");
require_once (SITEROOT . '/class/include.class.php');

require_once(SITEROOT . '/public.ssl/modules/actes/class/ActesEnvelope.class.php');

$actesTransactionsSQL = new ActesTransactionsSQL($sqlQuery);
$all = $actesTransactionsSQL->getArchiveFStatus(1);

 foreach($all as $transactionInfo){ 
	$id = $transactionInfo['id'];
	
	echo "\nAnalyse de la transaction $id ...\n";
	
	$trans = new ActesTransaction($id);
	$trans->init();
	$subject = $trans->get('subject');
	
	
	$files = $trans->fetchFilesList();
	$acte_file = $files[0]['name'];
		
	$env = new ActesEnvelope($trans->get('envelope_id'));
	$env->init();
	
	$file_path  = ACTES_FILES_UPLOAD_ROOT . "/" . $env->get('file_path');
	if (! file_exists($file_path)){
		echo "$file_path n'existe pas\n";
		continue;
	}
	
	$tmpDir = "/tmp/" . Helpers::genTempName();
	mkdir($tmpDir) or die("Impossible de créer un répertoire temporaire");
		  
	
	$cmd = "tar xzf $file_path  -C  $tmpDir ";
	Trace::wrap_exec($cmd, $status, $ret);
	
	$dom = new DomDocument();
	$dom->load($tmpDir."/".$acte_file);
	$r  = $dom->getElementsByTagName("Objet");
	
	$xml_subject = utf8_decode($r->item(0)->nodeValue);
	
	if ($subject != $xml_subject ){	
		echo "Ooops... le sujet $subject diffère du contenu de l'enveloppe {$xml_subject}...\n";
		$r->item(0)->nodeValue = utf8_encode(XML_escaping("$subject"));
		$dom->save($tmpDir."/".$acte_file) or die ("OOPS impossible de créer le fichier actes...");
		chdir($tmpDir);
	 	$cmd = "/bin/tar cf - * | /bin/gzip -9 > $file_path";
		Trace::wrap_exec($cmd, $status, $ret);
	 	
	} else {
		echo "Cette enveloppe est correcte\n";
	}
	
	$cmd = "rm -rf $tmpDir";
	Trace::wrap_exec($cmd, $status, $ret);
} 




