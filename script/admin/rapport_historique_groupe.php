<?php

require_once(__DIR__ . "/../../init/init.php");



function compte_actes($siren, $sqlQuery)
{
    $sql = "SELECT sum(file_size) as total,count(*) as nombre FROM actes_envelopes WHERE siren like ?";
    $actes = $sqlQuery->queryOne($sql, $siren);
    $message = '';
    $size = 0;
    $size = $actes["total"];
    echo "Taille totale des enveloppes actes : " . $size . " et nombre d'enveloppes " . $actes["nombre"] . "\n";

    return $size;
}

function compte_helios($authority_id, $sqlQuery)
{
    $sql = "select sum(file_size) as total ,count(*) as nombre FROM helios_transactions WHERE authority_id= ?";
    $helios = $sqlQuery->queryOne($sql, $authority_id);
    $message = '';
    $size = 0;
    $size = $helios["total"];
    echo "Taille totale des flux PES_ALLER : $size et le nombre de PES " . $helios["nombre"] . "\n";

    return $size;
}


$message = '';
$mail = EMAIL_ADMIN_TECHNIQUE;


$list_gpe = array('1','99','3','54','84','55','83','105','14','72','104','89','65','10','91','60','67','94','64','53','2','98','74','66','4','61','106','50');
$total = 0;
$units = 'BKMGTP';

foreach ($list_gpe as $gpe) {
    $sql = "select id from authorities where authority_group_id=?";
    $listidcoll = $sqlQuery->query($sql, $gpe);
    //var_dump($listidcoll);exit;
    foreach ($listidcoll as $id) {
        $authority_id = $id['id'];
        echo "coll id : " . $authority_id . "\n";
        //exit;

        $sql = "SELECT siren FROM authorities WHERE id= ?";
        $siren = $sqlQuery->queryOne($sql, $authority_id);
        echo "$siren\n";
        // ACTES
        $total += compte_actes($siren, $sqlQuery);

        // HELIOS
        $total += compte_helios($authority_id, $sqlQuery);
        echo $total . " --> $gpe --> " . $id['id'] . "\n";
        $factor = (int)(log($total, 1000));
        $size = sprintf("%.2f", $total / pow(1000, $factor)) . @$units[$factor];
        echo $size . "\n";
        //exit;
    }
}
echo $total . "\n";
$factor = (int)(log($total, 1000));
$size = sprintf("%.2f", $total / pow(1000, $factor)) . @$units[$factor];
echo $size;
