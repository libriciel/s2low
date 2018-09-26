<?php

require_once( __DIR__."/../../init/init.php");


function curlappel($URL,$login,$mdp,$post_data){
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl,CURLOPT_URL,$URL);
    curl_setopt($curl,CURLOPT_USERPWD,"$login:$mdp");
    curl_setopt($curl, CURLOPT_POST,true);
    curl_setopt($curl, CURLOPT_POSTFIELDS,$post_data);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    //curl_setopt($curl, CURLOPT_VERBOSE, true);
    
    $output = curl_exec($curl);
    
    if ($err = curl_error($curl)){
        echo "Error : " . $err;
    }
    
    //echo "Resultat : ".$output."\n";
    return $output;
}

function listdocumentpastell($id_e,$login,$mdp,$url,$flux){
    $URL=$url."/recherche-document.php";
    $post_data = array(
        'id_e'=>$id_e,
        'type'=>$flux,
        'lastetat'=>'verif-sae-erreur',
        'limit'=>50000
    );
    
    echo"$URL $login $mdp \n";
    $output=curlappel($URL,$login,$mdp,$post_data);
    return json_decode($output);
}

function verifsae($id_e,$login,$mdp,$url,$listdocument){
    $URL=$url."/action.php";
    foreach ($listdocument as $document) {
        echo $document->id_d."\n";
        $post_data = array(
            'action'=>'verif-sae',
            'id_d'=>$document->id_d,
            'id_e'=>$id_e
        );
        
        //exit;
        
        if($document->id_e == $id_e && $document->last_action == 'verif-sae-erreur'){
            echo "le document peut etre verif\n";
            $output=curlappel($URL,$login,$mdp,$post_data);
            var_dump($output);
        }
    }
    
}

$sql = "select pastell_id_e,pastell_login,pastell_password,pastell_url from authorities where pastell_url not like '' AND pastell_login not like '' AND pastell_password not like ''";

$list_col = $sqlQuery->query($sql);

//print_r($list_col);
//exit;

foreach($list_col as $col){
    
    $recherche=listdocumentpastell($col['pastell_id_e'],$col['pastell_login'],$col['pastell_password'],$col['pastell_url'],'actes-generique');
    
    if (!empty($recherche) && !array_key_exists('error-message', $recherche)) {
        //print_r($recherche);exit;
        verifsae($col['pastell_id_e'],$col['pastell_login'],$col['pastell_password'],$col['pastell_url'],$recherche);
    }
    
    
    $recherche=listdocumentpastell($col['pastell_id_e'],$col['pastell_login'],$col['pastell_password'],$col['pastell_url'],'helios-generique');
    
    if (!empty($recherche) && !array_key_exists('error-message', $recherche)) {
        verifsae($col['pastell_id_e'],$col['pastell_login'],$col['pastell_password'],$col['pastell_url'],$recherche);
    }
}