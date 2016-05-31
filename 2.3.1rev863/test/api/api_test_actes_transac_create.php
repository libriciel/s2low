<?php

// Url vers la plateforme s2low que vous voulez atteindre
$url = 'https://192.168.1.28/';


$api = $url . "/modules/actes/actes_transac_create.php";

define('PEM', 'user1.pem');
define('SSLKEY', 'user1-key.pem');
define('PASSWORD', 'user1');


$data = array(
	'api' => '1',
	'nature_code' => '3',
	'classif1' => '3',
	'classif2' => '3',
	'number' => 'NUMBER2',
	'decision_date' => '2015-05-21',
	'subject' => 'objet de acte',
	'acte_pdf_file' => NULL
);

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $api);
curl_setopt($ch, CURLOPT_POST, TRUE);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

curl_setopt($ch, CURLOPT_SSLCERT, PEM);
curl_setopt($ch, CURLOPT_SSLCERTPASSWD, PASSWORD);
curl_setopt($ch, CURLOPT_SSLKEY, SSLKEY);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

$curl_return = curl_exec($ch);
if ($curl_return === false) {
	echo 'KO\nErreur dans le module curl\n';
	echo 'curl_errno() = ' . curl_errno($ch) . "\n";
	echo 'curl_error() = ' . curl_error($ch) . "\n";
	echo $curl_log;
} else {
	echo 'Document envoyé [' . $curl_return . ']<br >';
}

curl_close($ch);

