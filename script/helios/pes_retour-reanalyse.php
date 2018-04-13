<?php

require_once( __DIR__."/../../init/init.php");
libxml_use_internal_errors(true);

// Script charge de reanalyser les PES_RETOUR

$file_list = scandir(HELIOS_RESPONSES_ERROR_PATH);

$file_list = array_diff($file_list, array('..', '.'));

if (!$file_list){
    return;
}
//var_dump($file_list);
$list_pesretour=array();
foreach($file_list as $file){
    try {
        $filepath=HELIOS_RESPONSES_ERROR_PATH."/$file";
        //echo $filepath."\n";
        libxml_clear_errors();
        $xml = simplexml_load_file($filepath);
        $root_name = strtolower($xml->getName());
        
        if ($root_name == 'pes_retour'){
            $siret=strval($xml->EnTetePES->IdColl["V"]);
            if(strlen($siret) == 14 )
                rename($filepath,HELIOS_FTP_RESPONSE_TMP_LOCAL_PATH."/".$file);
        }
    } catch (Exception $e){
        echo "ERREUR :".$e->getMessage();
    }
}
