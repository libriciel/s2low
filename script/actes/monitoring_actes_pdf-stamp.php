<?php

//Script tente de faire tamponner un PDF et contrôle l'empreinte SHA1 du PDF retourné


//RETOURNE 0 si tout va bien
//RETOURNE 2 si tout va mal
require_once( __DIR__."/../../init/init.php");

$email="s2low@libriciel.coop";
$subject="Apposition du cartouche";
$retour=0;
$message="OK";

$origfile_path=__DIR__."/../../test/TestPDF/pdf17o.pdf";
$origfiletampone_path=__DIR__."/../../test/TestPDF/pdf17o_tampon.pdf";
$file_tampone="/tmp/pdf17o_tampon.pdf";
$pdfStampData = new PDFStampData();
$pdfStampData->envoi_prefecture_date = "2018-01-15 00:00:00";
$pdfStampData->recu_prefecture_date = "2018-01-16 00:00:00";
$pdfStampData->affichage_date = "2018-01-17 00:00:00";
$pdfStampData->identifiant_unique = "034-491011698-20180116-TESTPDFSTAMP-DE";

$pdfStampWrapper = $objectInstancier->get('PDFStampWrapper');
$result =  $pdfStampWrapper->stamp($file_path, $pdfStampData);
file_put_contents($file_tampone,$result);

$pdftamponorig= new Imagick($origfiletampone_path);
$pdftampon= new Imagick($file_tampone);

if($md5sum != md5_file($file_tampone)){
    $message="CRITICAL : le md5sum est différent de celui attendu";
    $retour=2;
}

echo "$message\n";
exit($retour);
