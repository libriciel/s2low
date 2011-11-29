<?php
/*
 * L'application a générerer des fichiers XML sans objets...
 * 
 */

require_once ( __DIR__."/../../config/config.php");
require_once (SITEROOT . '/class/include.class.php');
require_once(SITEROOT . '/public.ssl/modules/actes/class/ActesEnvelope.class.php');
require_once (SITEROOT . '/public.ssl/modules/actes/class/ActesEnvelopeSerialSQL.class.php');


$id = 121;

$trans = new ActesTransaction($id);
$trans->init();

$files = $trans->fetchFilesList();

$acte_file = $files[0]['name'];

$subject = $trans->get('subject');

$env = new ActesEnvelope($trans->get('envelope_id'));
$env->init();

$file_path  = ACTES_FILES_UPLOAD_ROOT . "/" . $env->get('file_path');

$tmpDir = "/tmp/" . Helpers::genTempName();

mkdir($tmpDir) or die("Impossible de créer un répertoire temporaire");
	  
if (! file_exists($file_path)){
	die("$file_path n'existe pas");
}

$cmd = "tar xzf $file_path  -C  $tmpDir ";
Trace::wrap_exec($cmd, $status, $ret);

$dom = new DomDocument();
$dom->load($tmpDir."/".$acte_file);
$r  = $dom->getElementsByTagName("Objet");

if ($subject != $r->item(0)->nodeValue ){
	echo "Ooops... le sujet diffère du contenu de l'enveloppe...\n";
	$r->item(0)->nodeValue = "$subject";
	$dom->save($tmpDir."/".$acte_file);
	chdir($tmpDir);
 	$cmd = "/bin/tar cf - * | /bin/gzip -9 > $file_path";
	Trace::wrap_exec($cmd, $status, $ret);
 	
} else {
	echo "Cette enveloppe est correcte\n";
}

$cmd = "rm -rf $tmpDir";
Trace::wrap_exec($cmd, $status, $ret);





