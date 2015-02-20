<?php
$debut = microtime(true);
require_once( SITEROOT . '/class/Layout.class.php');
require_once( SITEROOT . '/class/User.class.php');
require_once( SITEROOT . '/class/Group.class.php');
require_once( SITEROOT . '/class/Authority.class.php');
require_once( SITEROOT . 'class/Database.class.php');
require_once( SITEROOT . 'class/Module.class.php');
require_once( SITEROOT . 'class/Log.class.php');
require_once( SITEROOT . 'class/Trace.class.php');
require_once( SITEROOT . 'class/ServiceUser.class.php');
require_once( SITEROOT . 'ext/mime_content_type.func.php');
require_once( SITEROOT . 'class/XMLHelper.php');
require_once( SITEROOT . 'class/JSONoutput.class.php');
require_once( SITEROOT . 'class/ModulePermission.class.php');


if (php_sapi_name() != 'cli'){
	session_start();
}

/*
 * Ce fichier à l'air d'être inclu dans tous les scripts S²low
 * 
 */
function hecho($message,$quot_style=ENT_QUOTES){
	echo get_hecho($message,$quot_style);
}

function get_hecho($message,$quot_style=ENT_QUOTES){
	return htmlspecialchars($message,$quot_style);
}

//Cette variable est utilisée partout sans être initialisé...
$html = "";

$jsonOutput = new JSONoutput();
