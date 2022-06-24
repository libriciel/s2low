<?php

require_once(__DIR__ . "/../../init/init.php");


$sql = "SELECT authorities.id,authorities.name,authorities.siren,department,authority_groups.name as group_name FROM authorities" .
    " JOIN authority_groups ON  authority_groups.id=authorities.authority_group_id" .
    " ORDER BY name";

$all = $sqlQuery->query($sql);
$nb_col = count($all);
echo "nb col = $nb_col\n";

foreach ($all as $nb_line => $line) {
    echo "{$line['name']} - {$line['id']} - $nb_line/$nb_col\n";

    $sql2 = "SELECT min(decision_date) as _min,max(decision_date) as max FROM actes_transactions WHERE authority_id=? GROUP BY authority_id";
    $actes = $sqlQuery->queryOne($sql2, $line['id']);
    $all[$nb_line]['min'] = $actes['_min'];
    $all[$nb_line]['max'] = $actes['max'];

    $sql2 = "SELECT min(submission_date) as _min,max(submission_date) as max FROM helios_transactions WHERE authority_id=? GROUP BY authority_id";
    $actes = $sqlQuery->queryOne($sql2, $line['id']);
    $all[$nb_line]['min2'] = $actes['_min'];
    $all[$nb_line]['max2'] = $actes['max'];
    print_r($all[$nb_line]);
}


echo "*****\n";
echo "nom,siren,departement,groupe,min(actes),max(actes),min(helios),max(helios)\n";
$csvOutput = new CSVOutput();
$csvOutput->display($all);
