#! /usr/bin/php

<?php

require_once(__DIR__ . "/../../init/init.php");

//CONSTANTES------------------------------------------------------------------------------------------------------------
private const CORRESPONDANCE_POSTE_COMPTABLE_FTP = [
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

private const COL = [
        "DATE" => 2,
        "SIRET" => 8,
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
    $siretAtraiter = [];
    $row = 0;
    if (($handle = fopen($nameFile, "r")) !== false) {
        while (($dataLigne = fgetcsv($handle, 1000, ";")) !== false) {
            $row++;
            if ($row == 1) {
                continue;
            }
            $message = "ligne $row";
            try {
                checkIfAllValuesAreDefined($dataLigne);
                $message = $message . " : " . $dataLigne[COL["SIRET"]] . " : " . $dataLigne[COL["SL_SOURCE"]] . "=>" . $dataLigne[COL["SL_CIBLE"]];
                checkIfLigneIsATraiter($dataLigne[COL["DATE"]], $dataLigne[COL["CHT_SL"]], $date);
                checkIfSiretIsAlreadyPresent($dataLigne[COL["SIRET"]], $siretAtraiter);
                $siretAtraiter[$dataLigne[COL["SIRET"]]] = ["SlSource" => $dataLigne[COL["SL_SOURCE"]],"SlCible" => $dataLigne[COL["SL_CIBLE"]]];
            } catch (Exception $e) {
                echo $message . " : " . $e->getMessage() . "\n";
                if (is_a($e, DomainException::class)) {
                    throw new Exception("Erreur Fatale");
                }
                continue;
            }
        }
        fclose($handle);
    }
    return $siretAtraiter;
}

function checkIfAllValuesAreDefined($dataLigne)
{
    foreach (COL as $nomColonne => $indiceColonne) {
        if (!isset($dataLigne[$indiceColonne])) {
            throw new Exception("Ligne mal définie rencontrée");            //TODO : rajouter le numéro de ligne
        }
    }
}

function checkIfLigneIsATraiter($dateLigne, $changeSL, $date)
{
    if ($dateLigne != $date) {
        throw new Exception("Autre date");
    }
    if ($changeSL != "OUI") {
        throw new Exception("SL inchangé");
    }
}

function checkIfSiretIsAlreadyPresent($siret, $array)
{
    if (in_array($siret, array_keys($array))) {
        throw new DomainException("Fichier incohérent, SIRET $siret en double");
    }
}

/**
 * @param object $sqlQuery
 * @param int $siret
 * @return int
 * @throws Exception
 */
function getAuthorityIdFromSiret(object $sqlQuery, $siret): int
{
    $infoAuthority = $sqlQuery->query("SELECT authority_id FROM authority_siret WHERE siret=? AND is_blocked=FALSE", $siret);

    if (! $infoAuthority) {
        throw new Exception("La collectivité $siret n'est pas abonnée à l'application Comptabilité Publique du TdT, elle n'est donc pas autorisée à recevoir le PES_Retour ");
    }

    if (count($infoAuthority) > 1) {
        throw new Exception("Le SIRET $siret est associé à plusieurs collectivités. Le PES_Retour n'est donc pas attribué");
    }

    return (int) $infoAuthority[0]['authority_id'];
}

//PROGRAMME-------------------------------------------------------------------------------------------------------------
// TRAITEMENT DES PARAMETRES
if (!in_array($argc, [3,4])) {
    echo "Usage : " . $argv[0] . " nomFichier date [confirmExecution]\n";
    echo "confirmExecution (optionnel) les modifs en BDD sont réalisée ssi ce paramètre vaut execute\n ";
    return -1;
}

$nameFile = $argv[1];

if (!is_file($nameFile)) {
    echo "$nameFile doit être un nom de fichier\n";
    return -2;
}

if (!is_readable($nameFile)) {
    echo "$nameFile n'est pas accessible en lecture\n";
    return -3;
}

$date = $argv[2];

$execute = false;
if (isset($argv[3]) && $argv[3] === "execute") {
    $execute = true;
}
if (!$execute) {
    echo "AUCUNE MODIFICATION NE SERA APPORTEE EN BDD\n";
}

echo "TRAITEMENT DU FICHIER-----------------------------------------------------------------------------------------\n";
$collectivitesATraiter = extractDataFromFile($nameFile, $date);

echo "EXTRACTION DES AUTORITES CORRESPONDANT AUX SIRETS------------------------------------------------------------\n";
$authorities = [];
foreach ($collectivitesATraiter as $siret => $collectivite) {
    $message = $siret . " : " . $collectivite["SlSource"] . "=>" . $collectivite["SlCible"];
    try {
        $idAuthority = getAuthorityIdFromSiret($sqlQuery, $siret);
        if (!in_array($idAuthority, array_keys($authorities))) {
            $authorities[$idAuthority] = ["sirets" => [$siret],"SlSource" => $collectivite["SlSource"],"SlCible" => $collectivite["SlCible"]];
        } else {
            if (
                $authorities[$idAuthority]["SlSource"] != $collectivite["SlSource"]
                ||
                $authorities[$idAuthority]["SlCible"] != $collectivite["SlCible"]
            ) {
                throw new DomainException("Fichier incohérent : deux collectivités dépendant de la même autorité ont des Sl différents");
            }
            $authorities[$idAuthority]["sirets"][] = $siret;
        }
    } catch (Throwable $e) {
        echo $message . " : KO : " . $e->getMessage() . "\n";
        if (is_a($e, DomainException::class)) {
            throw new Exception("Erreur Fatale");
        }
    }
}
// 3) Tous les Sl_Cibles doivent être égaux.

$bddAuthorities = [];

foreach ($authorities as $idAuthority => $arraySiren) {
    $authority = $sqlQuery->queryOne("SELECT id,name,helios_ftp_dest FROM authorities where id=?", $idAuthority);
    $resultsSirets = $sqlQuery->query("SELECT siret FROM authority_siret where authority_id=? AND is_blocked = FALSE", $idAuthority);
    $sirets = [];
    foreach ($resultsSirets as $siret) {
        $sirets[] = $siret["siret"];
    }
    $bddAuthorities[$authority["id"]] = [
            "name" => $authority["name"],
        "helios_ftp_dest" => $authority["helios_ftp_dest"],
        "sirets" => $sirets
    ];
}

//VERIFICATION DES AUTORITES. IL FAUT QUE
// 1) TOUS LES SIRETS D'UNE MËME AUTORITE SOIENT MIGRES
// 2) Chaque Sl_Source d'un siret corresponde au helios_ftp_dest de l'autorité
echo "TRAITEMENT des autorites--------------------------------------------------------------------------------------\n";
/**
 * @param $sirets1
 * @param $sirets2
 * @return bool
 */
function areEquals($sirets1, $sirets2): bool
{
    if (count($sirets1) != $sirets2) {
        return false;
    }
    foreach ($sirets1 as $siret) {
        if (!in_array($siret, $sirets2)) {
            return false;
        }
    }
}

foreach ($authorities as $id => $authority) {
    $bddAuthoritie = $bddAuthorities[$id];
    $action = $bddAuthoritie["name"] . " ( " . $id . " , " . $bddAuthoritie["helios_ftp_dest"] . ") " . CORRESPONDANCE_POSTE_COMPTABLE_FTP[$authority["SlSource"]] . "=>" . CORRESPONDANCE_POSTE_COMPTABLE_FTP[$authority["SlCible"]];
    try {
        if ($bddAuthoritie["helios_ftp_dest"] != CORRESPONDANCE_POSTE_COMPTABLE_FTP[$authority["SlSource"]]) {
            throw new Exception("helios_ftp_dest ne correspond pas à SlSource");
        }
        if (areEquals($bddAuthoritie["sirets"], $authority["sirets"])) {
            throw new Exception("La liste en BDD des sirets de l'authorité $id [" . implode(",", $bddAuthoritie["sirets"]) . "] ne correspond pas à l'ensemble des SIRETS présents dans le fichier [" . implode(",", $authority["sirets"]) . "]");
        }
        if ($bddAuthoritie["helios_ftp_dest"] != CORRESPONDANCE_POSTE_COMPTABLE_FTP[$authority["SlSource"]]) {
            throw new Exception("helios_ftp_dest ne correspond pas à SlSource");
        }

        $action = $bddAuthoritie["name"] . " ( " . $id . " ) " . $bddAuthoritie["helios_ftp_dest"] . "=>" . CORRESPONDANCE_POSTE_COMPTABLE_FTP[$authority["SlCible"]]; // TDO : check

        if ($execute) {
            $sqlQuery->query(
                "UPDATE authorities SET helios_ftp_dest=? WHERE id=?",
                $collectivite["SlCible"],
                $id
            );
        }
        echo $action . " : OK\n";

        if ($execute) {
            $sqlQuery->query(
                "UPDATE authorities SET helios_ftp_dest=? WHERE id=?",
                CORRESPONDANCE_POSTE_COMPTABLE_FTP[$collectivite["SlCible"]],
                $id
            );
        }
    } catch (Exception $e) {
        echo "$action : KO : " . $e->getMessage() . "\n";
    }
}
