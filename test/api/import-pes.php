<?php

$server = "https://localhost:4443/";


$post_data = array(
        "enveloppe" => "@/Users/eric/Desktop/pes_aller.xml",
);


$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $server . "/modules/helios/api/helios_importer_fichier.php");

curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_SSLCERT, "admin-cert.pem");
curl_setopt($ch, CURLOPT_SSLKEY, "admin-key.pem");
curl_setopt($ch, CURLOPT_USERPWD, "epommate2:winfield");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);


$data = curl_exec($ch);

if (! $data) {
    echo curl_error($ch);
}
curl_close($ch);

echo $data;
