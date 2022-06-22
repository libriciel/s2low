<?php

require_once(__DIR__ . "/../init/init.php");

$opensslTSWrapper = new OpensslTSWrapper(OPENSSL_PATH);
$soapClientFactory = new SoapClientFactory();

$openSign = new OpenSign($opensslTSWrapper, $soapClientFactory);

$openSign->setConfig(OPENSIGN_WSDL, OPENSIGN_CA, OPENSIGN_CRT, OPENSIGN_TIMEOUT);

$data = "Hello World !";
$reply = $openSign->getTimestampReply($data);
echo $openSign->verify($data, $reply);

echo $opensslTSWrapper->getTimestampReplyString($reply);
