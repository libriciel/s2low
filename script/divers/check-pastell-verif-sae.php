<?php

require_once( __DIR__."/../../init/init.php");

function verif_sae(PastellWrapper $pastell,$listdocument,$last_action){
    foreach ($listdocument as $document) {
        
        if($document['last_action'] == $last_action){
            echo "le document peut etre verif\n";
            $pastell->verifSAE($document['id_d']);
        }
    }

}

$sql = "select pastell_id_e,pastell_login,pastell_password,pastell_url from authorities where pastell_url not like '' AND pastell_login not like '' AND pastell_password not like ''";

$list_col = $sqlQuery->query($sql);

$pastellFactory = $objectInstancier->get(PastellWrapperFactory::class);

foreach($list_col as $col){

	$pastellProperties = new PastellProperties();
	$pastellProperties->id_e = $col['pastell_id_e'];
	$pastellProperties->url = $col['pastell_url'];
	$pastellProperties->login = $col['pastell_login'];
	$pastellProperties->password = $col['pastell_password'];

    $pastell =  $pastellFactory->getNewInstance($pastellProperties);
    //$etat='ar-recu-sae
    //$etat='send-archive';
    $etat='verif-sae-erreur';
    $recherche= $pastell->listDocuments('actes-generique',$etat);
    if (!empty($recherche) && !array_key_exists('error-message', $recherche)) {
        print_r($recherche);//exit;
        verif_sae($pastell,$recherche,$etat);
    }


    $recherche= $pastell->listDocuments('helios-generique');
    if (!empty($recherche) && !array_key_exists('error-message', $recherche)) {
        print_r($recherche);
        verifsae($pastell,$recherche);
    }
}
