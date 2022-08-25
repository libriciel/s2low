<?php

use S2lowLegacy\Class\CurlWrapper;

require_once(__DIR__ . "/../../init/init.php");
\S2lowLegacy\Class\LegacyObjectsManager::setLegacyObjectInstancier();


$user_id = 12745;

// la partie x509 du certificat :  openssl pkcs12 -in certificat.p12 -out client.pem -clcerts -nokeys
$old_certificate_pem = '/Users/eric/Desktop/old-cert.pem';

//  la clé privée du certificat :   openssl pkcs12 -in certificat.p12 -out key.pem -nocerts
$old_key_pem = '/Users/eric/Desktop/old-key.pem';

$login = "epommateau";
$password = '***REMOVED***';


$curlWrapper = new CurlWrapper();
$curlWrapper->httpAuthentication($login, $password);
$curlWrapper->dontVerifySSLCACert();
$curlWrapper->setClientCertificate($old_certificate_pem, $old_key_pem, false);


// Url vers la plateforme s2low que vous voulez atteindre
$url  = 'https://s2low.org';

$api = $url . "/admin/users/admin_user_detail.php?id=$user_id";


echo "Recuperation des autres id...\n";



$json = bibicurl($curlWrapper, $api, $login, $password);
$info = json_decode($json, true);

//print_r($info);


foreach ($info["other_id"] as $id) {
    if ($id == $user_id) {
        continue;
    }
    echo "Traitement de $id\n";
    $api = $url . "/admin/users/admin_user_detail.php?id=" . $id;
//      echo $api;
    $retourcurl = bibicurl($curlWrapper, $api, $login, $password);
    $userinfo = json_decode($retourcurl, true);
//      echo "ici";
//      print_r($userinfo);
//      exit;

    $apiedit = $url . "/admin/users/admin_user_edit_handler.php";
    $module = array();


    $data = array(
        "api"   =>  "1",
        "id"    =>  $id,
        "name"  =>  utf8_decode($userinfo["name"]),             //OK, on attaque l'API en ISO
        "givenname" =>  utf8_decode($userinfo["givenname"]),    // (conversion UTF-8)
        "email" =>  $userinfo["email"],
        "status"    =>  $userinfo["status"],
        "authority_id"  =>  $userinfo["authority_id"],
        "role"  =>  $userinfo["role"],
        "authority_group_id"    =>  $userinfo["authority_group_id"],
        "login" =>  $userinfo["login"],
        "certificate"   =>  new CURLFile("/Users/eric/Desktop/new-cert.pem")
    );
    foreach ($userinfo["module"] as $key => $value) {
        $data["perm_$key"] = $value;
    }

    print_r($data);

    bibicurl($curlWrapper, $apiedit, $login, $password, $data);
    //exit;
}


function bibicurl(CurlWrapper $curlWrapper, $url, $identifiant, $mdp, $info = null)
{

    echo "Calling $url...\n";

    if ($info) {
        $curlWrapper->setProperties(CURLOPT_POST, true);
        $curlWrapper->setProperties(CURLOPT_POSTFIELDS, $info);
    } else {
        $curlWrapper->setProperties(CURLOPT_POST, false);
    }
    return $curlWrapper->get($url);
}
