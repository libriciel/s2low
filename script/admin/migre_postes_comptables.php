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
$colSiret=8;
$colSlCible=11;

$row = 1;
if (($handle = fopen($nameFile, "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
        if($data[$colDate] === $date){
            echo "------------------------------------------------------------------------\n";
            echo "Date : ".$data[$colDate] . "\n";
            echo "Siret : ".$data[$colSiret] . "\n";
            echo "SlCible : ".$data[$colSlCible] . "\n";
            echo "FTPCible : ".$correspondancePosteComptableFTP[$data[$colSlCible]] . "\n";

            $idAuthority= $sqlQuery->queryOne("SELECT id FROM authorities where dia_siret=?",(int) $data[$colSiret]);

            if($execute && !empty($idAuthority)){
                $sqlQuery->query(
                    "UPDATE authorities SET helios_ftp_dest=? WHERE id=?",
                    $correspondancePosteComptableFTP[$data[$colSlCible]],
                    (int) $idAuthority
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
