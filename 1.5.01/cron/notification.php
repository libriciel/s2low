<?php 
require_once (dirname(__FILE__)."/../config/config.php");
require_once (SITEROOT . '/class/include.class.php');
require_once (SITEROOT . '/public.ssl/modules/actes/class/ActesEnvelope.class.php');
require_once (SITEROOT . '/public.ssl/modules/actes/class/ActesNotification.class.php');

$db = DatabasePool::getInstance();
$actesNotification = new ActesNotification($db);
$actesNotification->sendAutomaticNotification();
