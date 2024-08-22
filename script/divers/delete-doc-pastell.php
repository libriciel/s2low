<?php

use S2lowLegacy\Lib\SQLQuery;

require_once __DIR__ . '/../../init/init.php';

$sqlQuery = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(SQLQuery::class);

function curl_appel($URL, $login, $mdp, $post_data)
{
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_URL, $URL);
        curl_setopt($curl, CURLOPT_USERPWD, "$login:$mdp");
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        //curl_setopt($curl, CURLOPT_VERBOSE, true);

        $output = curl_exec($curl);

    if ($err = curl_error($curl)) {
        echo "Error : " . $err;
    }

        //echo "Resultat : ".$output."\n";
        return $output;
}

function list_document_pastell($id_e, $login, $mdp, $url, $flux, $etat)
{
    $URL = $url . "/recherche-document.php";
    $post_data = array(
                'id_e' => $id_e,
                'type' => $flux,
                'lastetat' => $etat,
                'limit' => 50000
        );

    echo "$URL $login $mdp \n";
    $output = curl_appel($URL, $login, $mdp, $post_data);
    return json_decode($output);
}

function verif_sae($id_e, $login, $mdp, $url, $listdocument)
{
    $URL = $url . "/action.php";
    foreach ($listdocument as $document) {
            echo $document->id_d . "\n";
            $post_data = array(
               'action' => 'suppression',
               'id_d' => $document->id_d,
               'id_e' => $id_e
            );

            //exit;

            if ($document->id_e == $id_e) {
                echo "le document peut etre supprime\n";
                $output = curl_appel($URL, $login, $mdp, $post_data);
                var_dump($output);
            }
    }
}

function sortir2($message, $code_erreur = -1)
{
    echo $message . PHP_EOL;
    exit($code_erreur);
}

if ($argc != 2) {
    sortir2("Usage:\n {$argv[0]} id de la collectivite");
}

$idcoll = (int) $argv[1];

$sql = <<<SQL
SELECT pastell_id_e,pastell_login,pastell_password,pastell_url
FROM authorities
WHERE pastell_url NOT LIKE ''
  AND pastell_login NOT LIKE ''
  AND pastell_password NOT LIKE ''
  AND id = ?
SQL;

$list_col = $sqlQuery->query($sql, $idcoll);

//print_r($list_col);exit;

foreach ($list_col as $col) {
    $recherche = list_document_pastell(
        $col['pastell_id_e'],
        $col['pastell_login'],
        $col['pastell_password'],
        $col['pastell_url'],
        'actes-generique',
        'modification'
    );

    if (!empty($recherche) && !array_key_exists('error-message', $recherche)) {
        //print_r($recherche);exit;
        verif_sae($col['pastell_id_e'], $col['pastell_login'], $col['pastell_password'], $col['pastell_url'], $recherche);
    }

    $recherche = list_document_pastell(
        $col['pastell_id_e'],
        $col['pastell_login'],
        $col['pastell_password'],
        $col['pastell_url'],
        'helios-generique',
        'modification'
    );

    if (!empty($recherche) && !array_key_exists('error-message', $recherche)) {
        verif_sae($col['pastell_id_e'], $col['pastell_login'], $col['pastell_password'], $col['pastell_url'], $recherche);
    }
}
