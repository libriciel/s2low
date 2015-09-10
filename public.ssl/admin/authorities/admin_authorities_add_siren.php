<?php
require_once( __DIR__ . "/../../../init/init.php");

$me = new User();

if (! $me->authenticate()) {
        $jsonOutput->displayErrorAndExit("Echec de l'authentification");
}

if (! $me->isGroupAdminOrSuper()) {
        $jsonOutput->displayErrorAndExit("Acces refuse");
}

if($me->isSuper()){
        $gid=Helpers::getVarFromGet("authority_group_id");
        if(!is_numeric($gid)){
                $jsonOutput->displayErrorAndExit("identifiant groupe invalide");
        }
        else{
                $authority_group_id =  $gid;
        }
} else {
        $authority_group_id = $me->get("authority_group_id");
}

$siren = Helpers::getVarFromGet("siren");
$theSiren  = new Siren();
if(VERIFICATION_SIREN){
        if (! $theSiren->isValid($siren)) {
                $jsonOutput->displayErrorAndExit("siren non valide");
        }
}

$authorityGroupSirenSQL = new AuthorityGroupSirenSQL($sqlQuery);
if ($authorityGroupSirenSQL->exist($authority_group_id,$siren)){
        $jsonOutput->displayErrorAndExit("siren deja present");
}
$authorityGroupSirenSQL->add($authority_group_id,$siren);
$result['status'] = 'ok';
$result['message'] = 'ajout reussi';
$jsonOutput->display($result);
