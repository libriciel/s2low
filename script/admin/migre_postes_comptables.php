<?php

require_once( __DIR__."/../../init/init.php");

/**
 * @param $nameFile
 * @param $date
 * @return array
 */
function extractDataFromFile($nameFile, $date): array
{
    // STRUCTURE DU FICHIER
    $colDate=2;
    $colSiren=9;
    $colLibelle=5;
    $colSlSource=3;
    $colSlCible=11;
    $colChangementSl=16;

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

    $collectivitesATraiter = [];
    if (($handle = fopen($nameFile, "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
            if ($data[$colDate] === $date) {
                // ECRIRE LA PARTIE GENERIQUE DU MESSAGE
                $message = $data[$colSiren] . " : " . $data[$colSlSource] . "=>" . $data[$colSlCible];

                // TESTER QUE LES FTPS SOURCES ET CIBLE EXISTENT BIEN
                if (
                    !isset($correspondancePosteComptableFTP[$data[$colSlSource]])
                    ||
                    !isset($correspondancePosteComptableFTP[$data[$colSlCible]])
                ) {
                    $messageErreur = "SL inconnu";
                    echo $message . " : KO : " . $messageErreur . "\n";
                    continue;
                }

                // TESTER SI SL EST MODIFIE
                if ($data[$colChangementSl] != "OUI") {
                    //$messageErreur = "Sl non modifié";
                    //echo $message . " : KO : " . $messageErreur . "\n";
                    continue;
                }

                //VERIFIER QUE CE N'EST PAS UN DOUBLON
                if (in_array($data[$colSiren], array_keys($collectivitesATraiter))) {
                    if (
                        $collectivitesATraiter[$data[$colSiren]]["SlSource"] != $correspondancePosteComptableFTP[$data[$colSlSource]]
                        ||
                        $collectivitesATraiter[$data[$colSiren]]["SlCible"] != $correspondancePosteComptableFTP[$data[$colSlCible]]
                    ) {
                        $messageErreur = "PB : fichier incohérent";
                        echo $message . " : KO : " . $messageErreur . "\n";
                        break;
                    }
                    continue;
                }
                $collectivitesATraiter[$data[$colSiren]]["SlSource"] = $correspondancePosteComptableFTP[$data[$colSlSource]];
                $collectivitesATraiter[$data[$colSiren]]["SlCible"] = $correspondancePosteComptableFTP[$data[$colSlCible]];
            }
        }
        fclose($handle);
    }
    return $collectivitesATraiter;
}

/**
 * @param object $sqlQuery
 * @param int $siren
 * @param string $message
 * @return array
 * @throws Exception
 */
function getAuthority(object $sqlQuery, int $siren, string $message): array
{
    $infoAuthority = $sqlQuery->queryOne("SELECT id,name,helios_ftp_dest FROM authorities where siren=?", (int)$siren);

    // TESTER QUE LA COLLECTIVITE EXISTE BIEN
    if (empty($infoAuthority)) {
        $messageErreur = "Collectivité inconnue";
        throw new Exception($message . " : KO : " . $messageErreur);
    }
    return $infoAuthority;
}

/**
 * @param $helios_ftp_dest
 * @param $helios_ftp_dest_source
 * @param string $message
 * @throws Exception
 */
function checkSlSource($helios_ftp_dest, $helios_ftp_dest_source, string $message): void
{
// TESTER QUE LE REPERTOIRE D'ORIGINE EST CORRECT
    if ($helios_ftp_dest != $helios_ftp_dest_source) {
        $messageErreur = "Le ftp_dest actuel " . $helios_ftp_dest . " ne correspond pas à celui spécifié " . $helios_ftp_dest_source;
        throw new Exception($message . " : KO : " . $messageErreur);
    }
}

// TRAITEMENT DES PARAMETRES
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

$collectivitesATraiter = extractDataFromFile($nameFile, $date);

foreach ($collectivitesATraiter as $siren=> $collectivite){
    try {
        $message = $siren . " : " . $collectivite["SlSource"] . "=>" . $collectivite["SlCible"];
        // RECUPERER LA COLLECTIVITE PAR SON SIREN

        $infoAuthority = getAuthority($sqlQuery, $siren, $message);
        checkSlSource($infoAuthority["helios_ftp_dest"], $collectivite["SlSource"], $message);

        // TOUT EST OK, ON EXPLICITE L'ACTION A FAIRE
        $action = $infoAuthority["name"] . " ( " . $infoAuthority["id"] . " ) " . $infoAuthority["helios_ftp_dest"] . "=>" . $collectivite["SlCible"];
        echo $message . " : OK : " . $action . "\n";
        // ON EXECUTE SI NECESSAIRE
        if ($execute) {
            $sqlQuery->query(
                "UPDATE authorities SET helios_ftp_dest=? WHERE id=?",
                $collectivite["SlCible"],
                (int)$infoAuthority["id"]
            );
        }
    }
    catch (Exception $e){
        echo $e->getMessage()."\n";
    }
}
