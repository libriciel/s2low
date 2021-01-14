#! /usr/bin/php

<?php

require_once( __DIR__."/../../init/init.php");

//CONSTANTES------------------------------------------------------------------------------------------------------------
const CORRESPONDANCE_POSTE_COMPTABLE_FTP = [
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

const COL = [
        "DATE" => 2,
        "SIREN" => 9,
        "SL_SOURCE" => 3,
        "SL_CIBLE" => 11,
        "CHT_SL" => 16
];

//FONCTIONS-------------------------------------------------------------------------------------------------------------
/**
 * @param $nameFile
 * @param $date
 * @return array
 */
function extractDataFromFile($nameFile, $date): array
{
    $collectivitesATraiter = [];
    $row = 0;
    if (($handle = fopen($nameFile, "r")) !== FALSE) {
        while (($dataLigne = fgetcsv($handle, 1000, ";")) !== FALSE) {
            $row++;
            if($row == 1){
                continue;
            }
            $message = "ligne $row";                   //TODO : modifier pour avoir le numéro de ligne
            try {
                checkIfAllValuesAreDefined($dataLigne);
                $message = $message . " : ". $dataLigne[COL["SIREN"]] . " : " . $dataLigne[COL["SL_SOURCE"]] . "=>" . $dataLigne[COL["SL_CIBLE"]];
                checkIfLigneIsATraiter($dataLigne[COL["DATE"]],$dataLigne[COL["CHT_SL"]],$date);
                $collectivitesATraiter = addCollectivite($dataLigne[COL["SIREN"]],$dataLigne[COL["SL_SOURCE"]], $dataLigne[COL["SL_CIBLE"]], $collectivitesATraiter);
            }
            catch (Exception $e){
                echo $message." : ".$e->getMessage() . "\n";
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

function checkIfAllValuesAreDefined($dataLigne){
    foreach (COL as $nomColonne=>$indiceColonne){
        if(!isset($dataLigne[$indiceColonne])){
            throw new Exception("Ligne mal définie rencontrée");            //TODO : rajouter le numéro de ligne
        }
    }
}

function checkIfLigneIsATraiter($dateLigne, $changeSL, $date){
    if ($dateLigne != $date ) {
        throw new Exception("Autre date");
    }
    if($changeSL != "OUI"){
       throw new Exception("SL inchangé");
    }
}
/**
 * @param array $data
 * @param $date
 * @param array $collectivitesATraiter
 * @return array
 * @throws Exception
 */
function addCollectivite($siren, $slSource, $slCible, array $collectivitesATraiter): array
{

    if (!isset(CORRESPONDANCE_POSTE_COMPTABLE_FTP[$slSource]) || !isset(CORRESPONDANCE_POSTE_COMPTABLE_FTP[$slCible])) {
        throw new Exception("SL inconnu");
    }

    if (in_array($siren, array_keys($collectivitesATraiter))) {
        if (
            $collectivitesATraiter[$siren]["SlSource"] != CORRESPONDANCE_POSTE_COMPTABLE_FTP[$slSource]
            ||
            $collectivitesATraiter[$siren]["SlCible"] != CORRESPONDANCE_POSTE_COMPTABLE_FTP[$slCible]
        ) {
            throw new DomainException("PB : fichier incohérent");
        }
        return $collectivitesATraiter;
    }
    $collectivitesATraiter[$siren]["SlSource"] = CORRESPONDANCE_POSTE_COMPTABLE_FTP[$slSource];
    $collectivitesATraiter[$siren]["SlCible"] = CORRESPONDANCE_POSTE_COMPTABLE_FTP[$slCible];
    return $collectivitesATraiter;
}

/**
 * @param object $sqlQuery
 * @param int $siren
 * @param string $message
 * @return array
 * @throws Exception
 */
function getAuthorityInfosFromSiren(object $sqlQuery, $siren, string $message): array
{
    $infoAuthority = $sqlQuery->queryOne("SELECT id,name,helios_ftp_dest FROM authorities where siren=?", $siren);

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
    echo "Usage : ".$argv[0]." nomFichier date [confirmExecution]\n";
    echo "confirmExecution (optionnel) les modifs en BDD sont réalisée ssi ce paramètre vaut execute\n ";
    return -1;
}

$nameFile = $argv[1];

if(!is_file($nameFile)){
    echo "$nameFile doit être un nom de fichier\n";
    return -2;
}

if(!is_readable($nameFile)){
    echo "$nameFile n'est pas accessible en lecture\n";
    return -3;
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
