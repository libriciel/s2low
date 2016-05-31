<?php
require_once(__DIR__."/../../../config/config.php");
require_once(SITEROOT . '/class/include.class.php');

require_once( __DIR__ . "/../../../class/APIDefinition.class.php");
require_once( __DIR__ . "/../../../class/YMLLoader.class.php");

$apiDefinition = new APIDefinition(SITEROOT."/public.ssl/modules/dia/api-definition.yml", new YMLLoader());


header("Content-type: text/xml");


echo $apiDefinition->getWSDL(WEBSITE_SSL . "/modules/dia/soap-service.php");
