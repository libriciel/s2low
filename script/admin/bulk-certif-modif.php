<?php

// TODO A refactoriser
// NE PAS UTILISER - MERCI


function bibicurl($url,$identifiant,$mdp,$info=null){
	$ch = curl_init();

	// Paramétrage des options curl
	curl_setopt($ch, CURLOPT_URL, $url);
	if ($info)
	{
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, @$info );
	}
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_CAPATH, CA_PATH);
	curl_setopt($ch, CURLOPT_SSLCERT, PEM);
//    curl_setopt($ch, CURLOPT_SSLCERTPASSWD, PASSWORD);
	curl_setopt($ch, CURLOPT_SSLKEY,  SSLKEY);
//    curl_setopt($ch, CURLOPT_VERBOSE, true);
//    curl_setopt($ch, CURLOPT_NOPROGRESS, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLINFO_SSL_VERIFYRESULT, false);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
	curl_setopt($ch, CURLOPT_USERPWD, "$identifiant:$mdp");
	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

	$curl_return = curl_exec($ch);
	if ($curl_return === false) {
		echo 'KO\nErreur dans le module curl.' . '<br /gt;'."\n";
		echo 'curl_errno() = ' . curl_errno($ch) . '<br /gt;'."\n";
		echo 'curl_error() = ' . curl_error($ch) . '<br /gt;'."\n";
		exit;
	}

	curl_close($ch);
	$json=$curl_return;

	return $json;
}
    // Url vers la plateforme s2low que vous voulez atteindre
    $url  = 'https://s2low.org';
    #$api = $url."/modules/actes/actes_transac_get_status.php?transaction=657&message=1";
    $api = $url."/admin/users/admin_user_detail.php?id=12745";

    // la partie x509 du certificat :  openssl pkcs12 -in certificat.p12 -out client.pem -clcerts -nokeys
    define('PEM','old-cert.pem');
    //  la clé privée du certificat :   openssl pkcs12 -in certificat.p12 -out key.pem -nocerts
    define('SSLKEY','cdg85-key.pem');

    //le certificat du CA :           openssl pkcs12 -in certificat.p12 -out ca.pem -cacerts -nokeys
    define('CA_PATH',  './');

    define('PASSWORD', 'epommateau');

   $json = bibicurl($api,"epommateau","XXXXXX");
   $info = json_decode($json, true);
#echo "recuperation des autres id";
print_r($info);



	foreach($info["other_id"] as $id){

		if ($id == 12745){
			continue;
		}
		echo "Traitement de $id\n";
		$api=$url."/admin/users/admin_user_detail.php?id=".$id;
//		echo $api;
		$retourcurl = bibicurl($api,"epommateau","XXXX");
		$userinfo=json_decode($retourcurl, true);
//		echo "ici";
//		print_r($userinfo);
//		exit;

		$apiedit=$url."/admin/users/admin_user_edit_handler.php";
		$module=array();


		$data= array(
			"api"	=>	"1",
			"id"	=>	$id,
			"name"	=>	utf8_decode($userinfo["name"]),
			"givenname"	=>	utf8_decode($userinfo["givenname"]),
			"email"	=>	$userinfo["email"],
			"status"	=>	$userinfo["status"],
			"authority_id"	=>	$userinfo["authority_id"],
			"role"	=>	$userinfo["role"],
			"authority_group_id"	=>	$userinfo["authority_group_id"],
			"login"	=>	$userinfo["login"],
//			"password"	=>	$userinfo["password"],
//			"password2"	=>	$userinfo["password2"],
			"certificate"	=>	new CURLFile("./cdg85.pem")
		);
		foreach ($userinfo["module"] as $key => $value) {
			$data["perm_$key"] = $value;
		}
		echo $apiedit;
		print_r($data);
		//exit;

		bibicurl($apiedit,"epommateau","XXX",$data);
		//exit;
	}


