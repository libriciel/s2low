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
require_once( SITEROOT . 'class/ModulePermission.class.php');


if (php_sapi_name() != 'cli'){
	session_start();
}

/*
 * Ce fichier à l'air d'être inclu dans tous les scripts S²low
 * 
 */
function hecho($message,$quot_style=ENT_QUOTES){
	echo get_hecho($message,$quot_style,"iso-8859-15");
}

function get_hecho($message,$quot_style=ENT_QUOTES,$encoding="iso-8859-15"){
	return htmlspecialchars($message,$quot_style,$encoding);
}

if (defined("TESTING_ENVIRONNEMENT") && TESTING_ENVIRONNEMENT) {

    function exit_wrapper($status = "") {
        $message = "exit() called";
        if ($status){
            $message.=" with status $status";
        }
        throw new Exception($message);
    }

    function header_wrapper($string, $replace = true, $http_response_code = null) {
        echo "header('$string','$replace','$http_response_code') called";
    }

} else {

    function exit_wrapper($status = "")
    {
        exit($status);
    }

    function header_wrapper($string, $replace = true, $http_response_code = null)
    {
        header($string, $replace, $http_response_code);
    }
}


//Cette variable est utilisée partout sans être initialisé...
$html = "";

$jsonOutput = new JSONoutput();



