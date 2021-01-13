<?php

require_once( __DIR__."/../../init/init.php");

$correspondancePosteComptableFTP = [
    "SL1V"=>"VHPCE11",
    "SL2V"=>"VHPCE21",
    "SL3V"=>"VHPCE31",
    "SL5V"=>"VHPCE51",
    "SL1M"=>"MHPCE11",
    "SL2M"=>"MHPCE21",
    "SL3M"=>"MHPCE31",
    "SL4M"=>"MHPCE41",
    "SL5M"=>"MHPCE51"
    ];

if(!in_array($argc,[3,4])){
    var_dump($argc);
    echo "Usage : ".$argv[0]." nomFichier date [confirmExecution]\n";
    echo "confirmExecution (optionnel) les modifs en BDD sont réalisée ssi ce paramètre vaut execute\n ";
    return -1;
}

$nameFile = $argv[1];

if(!is_file($nameFile)){
    echo "$nameFile doit être un nom de fichier\n";
    return -2;
}
$date = $argv[2];

$execute = false;
if(isset($argv[3])&&$argv[3]==="execute"){
    $execute = true;
}

$colDate=2;
$colSiren=9;
$colLibelle=5;
$colSlOrigine=3;
$colSlCible=11;
$colChangementSl=16;

if (($handle = fopen($nameFile, "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
        if($data[$colDate] === $date){
            //ECRIRE LA PARTIE GENERIQUE DU MESSAGE
            $message = $data[$colLibelle]." ( ".$data[$colSiren]." ) : ".$data[$colSlOrigine]."=>".$data[$colSlCible];

            //TESTER QUE LES FTPS SOURCES ET CIBLE EXISTENT BIEN
            if(
                !isset($correspondancePosteComptableFTP[$data[$colSlOrigine]])
                ||
                !isset($correspondancePosteComptableFTP[$data[$colSlCible]])
            ){
              $messageErreur = "SL inconnu";
              echo $message." : KO : ".$messageErreur."\n";
              continue;
            }

            //TESTER SI SL EST MODIFIE
            if($data[$colChangementSl]!="OUI"){
                $messageErreur = "Sl non modifié";
                echo $message." : KO : ".$messageErreur."\n";
                continue;
            }

            //RECUPERER LA COLLECTIVITE PAR SON SIREN
            $infoAuthority= $sqlQuery->queryOne("SELECT id,name,helios_ftp_dest FROM authorities where siren=?",(int) $data[$colSiren]);

            //TESTER QUE LA COLLECTIVITE EXISTE BIEN
            if(empty($infoAuthority)){
                $messageErreur = "Collectivité inconnue";
                echo $message." : KO : ".$messageErreur."\n";
                continue;
            }

            //TESTER QUE LE REPERTOIRE D'ORIGINE EST CORRECT
            if($infoAuthority["helios_ftp_dest"] != $correspondancePosteComptableFTP[$data[$colSlOrigine]]) {
                $messageErreur = "Le ftp_dest actuel ".$infoAuthority["helios_ftp_dest"]." ne correspond pas à celui spécifié ".$correspondancePosteComptableFTP[$data[$colSlOrigine]];
                echo $message . " : KO : " . $messageErreur."\n";
                continue;
            }
            $action = $infoAuthority["name"]." ( ".$infoAuthority["id"]." ) ".$infoAuthority["helios_ftp_dest"]."=>".$correspondancePosteComptableFTP[$data[$colSlCible]];
            echo $message." : OK : ".$action."\n";
            // TOUT EST OK, ON EXECUTE SI NECESSAIRE
            if($execute){
                $sqlQuery->query(
                        "UPDATE authorities SET helios_ftp_dest=? WHERE id=?",
                        $correspondancePosteComptableFTP[$data[$colSlCible]],
                        (int) $infoAuthority["id"]
                );
            }
        }
    }
    fclose($handle);
}
