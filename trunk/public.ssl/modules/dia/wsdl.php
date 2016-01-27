<?php
require_once( __DIR__ . "/../../../init/init-www-dia.php");

$apiDefinition = new APIDefinition(__DIR__."/api-definition.yml", new YMLLoader());


header("Content-type: text/xml");


echo $apiDefinition->getWSDL(WEBSITE_SSL . "/modules/dia/soap-service.php");
