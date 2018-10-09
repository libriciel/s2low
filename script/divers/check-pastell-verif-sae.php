<?php

require_once( __DIR__."/../../init/init.php");

function verifsae($pastell,$listdocument){
    foreach ($listdocument as $document) {
        //echo "Nouveau document :\n";
        //print_r($document);
        
        if($document['last_action'] == 'verif-sae-erreur'){
            echo "le document peut etre verif\n";
            $pastell->verifSAE($document['id_d']);
            //exit;
        }
    }
    
}

$sql = "select pastell_id_e,pastell_login,pastell_password,pastell_url from authorities where pastell_url not like '' AND pastell_login not like '' AND pastell_password not like ''";

$list_col = $sqlQuery->query($sql);

//print_r($list_col);
//exit;
$pastellFactory = new PastellFactory();

foreach($list_col as $col){
    
    $pastell =  $pastellFactory->getNewInstance(
        $col['pastell_url'],
        $col['pastell_id_e'],
        $col['pastell_login'],
        $col['pastell_password']
        );
    $recherche= $pastell->listDocuments('actes-generique');
    if (!empty($recherche) && !array_key_exists('error-message', $recherche)) {
        print_r($recherche);//exit;
        verifsae($pastell,$recherche);
    }
    
    
    //    $recherche=listdocumentpastell($col['pastell_id_e'],$col['pastell_login'],$col['pastell_password'],$col['pastell_url'],'helios-generique');
    
    $recherche= $pastell->listDocuments('helios-generique');
    if (!empty($recherche) && !array_key_exists('error-message', $recherche)) {
        print_r($recherche);
        verifsae($pastell,$recherche);
    }
}
