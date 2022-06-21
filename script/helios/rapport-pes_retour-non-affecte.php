<?php
require_once( __DIR__."/../../init/init.php");
libxml_use_internal_errors(true);

// Script charge de récuprer la liste distincte des SIRET des PES_RETOUR
// dans le dossier HELIOS_RESPONSES_ERROR_PATH

$file_list = scandir(HELIOS_RESPONSES_ERROR_PATH);

$file_list = array_diff($file_list, array('..', '.'));

if (!$file_list){
    return;
}
//var_dump($file_list);
$list_siret=array();
foreach($file_list as $file){
    try {
        $filepath=HELIOS_RESPONSES_ERROR_PATH."/$file";
        //echo $filepath."\n";
        libxml_clear_errors();
        $xml = simplexml_load_file($filepath);
        if (! $xml){
            throw new Exception("Le fichier n'est pas bien formé (fichier ignoré)");
        }
        $root_name = mb_strtolower($xml->getName());
        
        if ($root_name == 'pes_retour'){
            //echo "c'est un PES RETOUR\n";
            //var_dump($xml->EnTetePES);exit;
            $siret=strval($xml->EnTetePES->IdColl["V"]);
            if((! in_array($siret,$list_siret)) && (mb_strlen($siret) == 14 ))
                array_push($list_siret,$siret);
        }
    } catch (Exception $e){
        echo "ERREUR :".$e->getMessage();
    }
}

//var_dump($list_siret);

$list_coll=array();

$message='';
foreach($list_siret as $siret){
    $sql = "SELECT id,name FROM authorities WHERE id IN (SELECT authority_id FROM authority_siret WHERE siret like '".$siret."')";
    $authority_id=$sqlQuery->query($sql);
    $message .= "Le SIRET $siret est associe aux collectivites :\n";
    //var_dump($authority_id);
    
    $i=0;
    foreach($authority_id as $coll){
        /*
         $list_coll[$siret][$i]['id']=strval($coll['id']);
         $list_coll[$siret][$i]['name']=strval($coll['name']);
         */
        $message .= strval($coll['name']) ." Acces direct a la gestion de ses SIRET : ".WEBSITE_SSL."/admin/authorities/admin_authority_siret.php?id=".strval($coll['id'])."\n";
    }
    $message .= "\n -------------------------------------------------- \n";
}
//print_r($list_coll);
$message .= "Vous avez jusqu'à 11h59 pour traiter ces SIRET";
mail(EMAIL_ADMIN_TECHNIQUE,"Rapport sur les PES_RETOUR non affectable",$message,"from: ".EMAIL_ADMIN);
