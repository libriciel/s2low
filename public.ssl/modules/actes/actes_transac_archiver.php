<?php


// Configuration
require_once ("../../../config/config.php");
require_once (SITEROOT . '/class/include.class.php');
require_once (SITEROOT . '/public.ssl/modules/actes/class/ActesEnvelope.class.php');

$id = Helpers :: getVarFromPost("id");
$trans = new ActesTransaction();
$trans->setId($id);
 if ( ! $trans->init()) {
    $_SESSION["error"] = "Erreur d'initialisation de la transaction.";
    header("Location: " . WEBSITE_SSL . "/modules/actes/index.php");
    exit ();
}
$envelope = new ActesEnvelope($trans->get("envelope_id"));
$envelope->init();

$file_to_send =  ACTES_FILES_UPLOAD_ROOT . "/" .  $envelope->get("file_path");

// Définition des constantes
define ('ASALAE_WSDL',     'http://testasalae.dev.adullact.org/webservices/wsdl');
define ('IDENTIFIANT_VERSANT',   'TDT');
define ('MOT_DE_PASSE',    'TDTMOTDEPASSE');
define ('IDENTIFIANT_ARCHIVE',   '227806460');
define ('NUMERO_AGREMENT', 'ACCORDWD01');



//-------TODO : Quel information doit-on prendre ? 
$identifiant_producteur = 'tedetis'; // a déterminer : identifiant du service producteur des données
$objet_archive      = 'ACTES - transaction ' . $trans->get("number");
$numero_archive     = $trans->get("number");
$provenance_archive = 'tedetis';
//-------

$document           =  array('Attachment' => array(
                                '@attributes' => array(
                                    'format'=>'fmt/18',
                                    'mimeCode'=>'application/pdf',
                                    'filename'=>'archive.pdf'),
                                '@value'=>''),
                             'Description'=>'Acte',
                             'Type'  => array(
                                '@attributes' => array(
                                   'listVersionID' => 'edition 2009'),
                                '@value' => 'CDO'));

//  Description du contenu du fichier SEDA
$options = array( 'TransferIdentifier' => $num_archive,
                      'Comment'            => utf8_encode($objet_archive),
                      'TransferringAgency' => array('Identification' => IDENTIFIANT_VERSANT),
                      'ArchivalAgency'     => array('Identification' => IDENTIFIANT_ARCHIVE),
                      'Contains'           => array( 'ArchivalAgreement' => NUMERO_AGREMENT,
                                                     'DescriptionLanguage' => array(
                                                     	'@attributes' => array('listVersionID' => 'edition 2009'),
                                                     	'@value' => 'fr'),
                                                     'DescriptionLevel' => array(
                                                     	'@attributes' => array('listVersionID' => 'edition 2009'),
                                                     	'@value' => 'file'),
                                                     'Name'=> utf8_encode($objet_archive),
                                                     'ContentDescription' => array('CustodialHistory' => utf8_encode($provenance_archive),
                                                                                   'Description' => utf8_encode($objet_archive),
                                                                                   'DescriptionAudience'  => 'external',
                                                                                   'Language' => array(
                                                                                      '@attributes' => array('listVersionID' => 'edition 2009'),
                                                                                      '@value' => 'fr'),
                                                                                   'OriginatingAgency'     => array('Identification'=>$identifiant_producteur),
                                                                                                                    'ContentDescriptive' => array( 'KeywordAudience'=>'external',
                                                                                                                                               'KeywordContent' =>'Deliberation',
                                                                                                                                               'KeywordReference' =>'1',
                                                                                                                                               'KeywordType' => 'genreform'),
                                                                                                                    'Appraisal'=>array('Code'=>'001C',
                                                                                                                                       'StartDate'=>date('c'))),
                                                                                   'Document' => $document)
                                                   );

// Constructeur SoapClient 
$client = new SoapClient(ASALAE_WSDL);

// Appel de la fonctions SOAP : génération du fichier SEDA 
$seda = @$client->__soapCall("wsGSeda", array($options, IDENTIFIANT_VERSANT, MOT_DE_PASSE));

//-------TODO et quel fichier ?
$document  = file_get_contents($file_to_send);
//------

//TODO Je ne sais pas à quoi correpondent les arguments
// Appel de la fonctions SOAP : dépôt de l'archive
$retour  = @$client->__soapCall("wsDepot", array("bordereau.xml", $seda, "versement.tgz", $document, IDENTIFIANT_VERSANT, MOT_DE_PASSE));


//TODO : 
$trans->set("archive_url", 'http://www.google.fr');
$trans->save();

$_SESSION["error"] = "Transaction archivée avec succès";
header("Location: actes_transac_show.php?id=$id");