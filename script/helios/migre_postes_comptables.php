<?php

//CONSTANTES------------------------------------------------------------------------------------------------------------
const CORRESPONDANCEPOSTECOMPTABLEFTP = [
    "SL1V" => "VHPCE11",
    "SL2V" => "VHPCE21",
    "SL3V" => "VHPCE31",
    "SL5V" => "VHPCE51",
    "SL1M" => "MHPCE11",
    "SL2M" => "MHPCE21",
    "SL3M" => "MHPCE31",
    "SL4M" => "MHPCE41",
    "SL5M" => "MHPCE51"
];

const COL_DATE = 2;
const COL_SIREN = 9;
const COL_SL_SOURCE = 3;
const COL_SL_CIBLE = 11;
const COL_CHT_SL = 16;
require_once( __DIR__."/../../init/init.php");

//FONCTIONS-------------------------------------------------------------------------------------------------------------
/**
 * @param $nameFile
 * @param $date
 * @return array
 */
function extractDataFromFile($nameFile, $date): array
{
    $collectivitesATraiter = [];
    if (($handle = fopen($nameFile, "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
            try {
                if(isLigneATraiter($data[COL_DATE],$data[COL_CHT_SL],$date)){
                    $collectivitesATraiter = addCollectivité($data[COL_SIREN],$data[COL_SL_SOURCE], $data[COL_SL_CIBLE], $collectivitesATraiter);
                }
            }
            catch (Exception $e){
                echo $e->getMessage() . "\n";
                if(is_a($e,DomainException::class)){
                    break;
                }
                continue;
            }
        }
        fclose($handle);
    }
    return $collectivitesATraiter;
}

function isLigneATraiter($dateLigne,$changeSL,$date){
    if ($dateLigne != $date || $changeSL != "OUI") {
        return false;
    }
    return true;
}
/**
 * @param array $data
 * @param $date
 * @param array $collectivitesATraiter
 * @return array
 * @throws Exception
 */
function addCollectivité($siren, $slSource, $slCible, array $collectivitesATraiter): array
{
    $message = $siren . " : " . $slSource . "=>" . $slCible;

    if (!isset(CORRESPONDANCEPOSTECOMPTABLEFTP[$slSource]) || !isset(CORRESPONDANCEPOSTECOMPTABLEFTP[$slCible])) {
        $messageErreur = "SL inconnu";
        throw new Exception($message . " : KO : " . $messageErreur);
    }

    if (in_array($siren, array_keys($collectivitesATraiter))) {
        if (
            $collectivitesATraiter[$siren]["SlSource"] != CORRESPONDANCEPOSTECOMPTABLEFTP[$slSource]
            ||
            $collectivitesATraiter[$siren]["SlCible"] != CORRESPONDANCEPOSTECOMPTABLEFTP[$slCible]
        ) {
            $messageErreur = "PB : fichier incohérent";
            throw new DomainException($message . " : KO : " . $messageErreur);
        }
        return $collectivitesATraiter;
    }
    $collectivitesATraiter[$siren]["SlSource"] = CORRESPONDANCEPOSTECOMPTABLEFTP[$slSource];
    $collectivitesATraiter[$siren]["SlCible"] = CORRESPONDANCEPOSTECOMPTABLEFTP[$slCible];
    return $collectivitesATraiter;
}

/**
 * @param object $sqlQuery
 * @param int $siren
 * @param string $message
 * @return array
 * @throws Exception
 */
function getAuthorityInfosFromSiren(object $sqlQuery, int $siren, string $message): array
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
    if ($helios_ftp_dest != $helios_ftp_dest_source) {
        $messageErreur = "Le ftp_dest actuel " . $helios_ftp_dest . " ne correspond pas à celui spécifié " . $helios_ftp_dest_source;
        throw new Exception($message . " : KO : " . $messageErreur);
    }
}

//PROGRAMME-------------------------------------------------------------------------------------------------------------
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
if(!$execute){
    echo "AUCUNE MODIFICATION NE SERA APPORTEE EN BDD\n";
}

echo "TRAITEMENT DU FICHIER-----------------------------------------------------------------------------------------\n";
$collectivitesATraiter = extractDataFromFile($nameFile, $date);

echo "TRAITEMENT DES COLLECTIVITES----------------------------------------------------------------------------------\n";

foreach ($collectivitesATraiter as $siren=> $collectivite){
    try {
        $message = $siren . " : " . $collectivite["SlSource"] . "=>" . $collectivite["SlCible"];

        $infoAuthority = getAuthorityInfosFromSiren($sqlQuery, $siren, $message);
        checkSlSource($infoAuthority["helios_ftp_dest"], $collectivite["SlSource"], $message);

        $action = $infoAuthority["name"] . " ( " . $infoAuthority["id"] . " ) " . $infoAuthority["helios_ftp_dest"] . "=>" . $collectivite["SlCible"];
        echo $message . " : OK : " . $action . "\n";

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
