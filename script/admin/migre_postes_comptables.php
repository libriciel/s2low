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

$nameFile = $argv[1];
$date = $argv[2];
$execute = true; // TODO Récupérer depuis la ligne de commande

// Options :
//    $1 : nom du fichier
//    $2 : date
//    $3 : dry run ou pas ( à rajouter)

$colDate=2;
$colSiren=9;
$colLibelle=5;
$colSlOrigine=3;
$colSlCible=11;
$colChangementSl=16;

$row = 1;
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
              echo $message." : ".$messageErreur."\n";
              continue;
            }

            //TESTER SI SL EST MODIFIE
            if($data[$colChangementSl]!="OUI"){
                $messageErreur = "Sl non modifié";
                echo $message." : ".$messageErreur."\n";
                continue;
            }

            //RECUPERER LA COLLECTIVITE PAR SON SIREN
            $infoAuthority= $sqlQuery->queryOne("SELECT id,name,helios_ftp_dest FROM authorities where siren=?",(int) $data[$colSiren]);

            //TESTER QUE LA COLLECTIVITE EXISTE BIEN
            if(empty($infoAuthority)){
                $messageErreur = "Collectivité inconnue";
                echo $message." : ".$messageErreur."\n";
                continue;
            }

            //TESTER QUE LE REPERTOIRE D'ORIGINE EST CORRECT
            if($infoAuthority["helios_ftp_dest"] != $correspondancePosteComptableFTP[$data[$colSlOrigine]]) {
                $messageErreur = "Le ftp_dest actuel ".$infoAuthority["helios_ftp_dest"]." ne correspond pas à celui spécifié ".$correspondancePosteComptableFTP[$data[$colSlOrigine]];
                echo $message . " : " . $messageErreur."\n";
                continue;
            }
             
            // TOUT EST OK, ON EXECUTE SI NECESSAIRE
            if($execute){
                    $action = $infoAuthority["name"]." ( ".$infoAuthority["id"]." ) ".$infoAuthority["helios_ftp_dest"]."=>".$correspondancePosteComptableFTP[$data[$colSlCible]];
                    echo $message." : ".$action."\n";
                    $a=$sqlQuery->query(
                        "UPDATE authorities SET helios_ftp_dest=? WHERE id=?",
                        $correspondancePosteComptableFTP[$data[$colSlCible]],
                        (int) $infoAuthority["id"]
                    );
            }
        }
    }
    fclose($handle);
}
//Charger un fichier csv
//Pour chaque ligne
//-> vérifier que la date est ok (comparer col 2 à la date)
//-> prendre le SIRET (col 8 )
//-> rechercher l'id de l'autorité correspondante
//      $sql = "SELECT id FROM authorities where dia_siret=?";
//      return $this->queryOne($sql,$siret);
//-> modifier le poste comptable
//      helios_ftp_dest dans la table authorities
