<?php

require_once("TestTedetisApi.class.php");


define("SSL_CERTIFICAT_USER","/etc/apache2/ssl/user-cert.pem");
define("SSL_CERTIFICAT_KEY","/etc/apache2/ssl/user-key.pem");
define("SSL_CERTIFICAT_PASSWORD","winfield");
define("TEDETIS_URL","https://192.168.1.87/modules/actes/");


$testApi = new TestTedetisApi(TEDETIS_URL);

$testApi->setCertificate(SSL_CERTIFICAT_USER,SSL_CERTIFICAT_KEY,SSL_CERTIFICAT_PASSWORD);