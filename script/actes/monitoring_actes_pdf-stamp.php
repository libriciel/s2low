<?php

//Marche plus car il faut image-magick


//Script tente de faire tamponner un PDF et contrôle l'empreinte SHA1 du PDF retourné


//RETOURNE 0 si tout va bien
//RETOURNE 2 si tout va mal
use S2lowLegacy\Class\PDFStampData;
use S2lowLegacy\Class\PDFStampWrapper;
use S2lowLegacy\Lib\ObjectInstancier;

require_once(__DIR__ . "/../../init/init.php");
$objectInstancier = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(ObjectInstancier::class);

$email = EMAIL_ADMIN_TECHNIQUE;
$subject = "Apposition du cartouche";
$retour = 0;
$message = "OK";

$origfile_path = __DIR__ . "/../../test/TestPDF/pdf17o.pdf";
$origfiletampone_path = __DIR__ . "/../../test/TestPDF/pdf17o-tampon.pdf";
$file_tampone = "/tmp/pdf17o_tampon.pdf";
$pdfStampData = new PDFStampData();
$pdfStampData->envoi_prefecture_date = "2018-01-15 00:00:00";
$pdfStampData->recu_prefecture_date = "2018-01-16 00:00:00";
$pdfStampData->affichage_date = "2018-01-17 00:00:00";
$pdfStampData->identifiant_unique = "034-491011698-20180116-TESTPDFSTAMP-DE";

$pdfStampWrapper = $objectInstancier->get(PDFStampWrapper::class);
$result =  $pdfStampWrapper->stamp($origfile_path, $pdfStampData);
file_put_contents($file_tampone, $result);

$pdftamponorig = new Imagick($origfiletampone_path);
$pdftampon = new Imagick($file_tampone);
$result = $pdftamponorig->compareImages($pdftampon, \Imagick::METRIC_MEANSQUAREERROR);
if ($result[1] != 0) {
    $message = "CRITICAL : le pdf tampone n est pas celui attendu";
    $retour = 2;
}
//print_r($result);
echo "$message\n";
exit($retour);
