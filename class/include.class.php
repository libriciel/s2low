<?php

require_once(SITEROOT . '/class/Layout.class.php');
require_once(SITEROOT . '/class/User.class.php');
require_once(SITEROOT . '/class/Group.class.php');
require_once(SITEROOT . '/class/Authority.class.php');
require_once(SITEROOT . 'class/Database.class.php');
require_once(SITEROOT . 'class/Module.class.php');
require_once(SITEROOT . 'class/Log.class.php');
require_once(SITEROOT . 'class/Trace.class.php');
require_once(SITEROOT . 'class/ServiceUser.class.php');
require_once( SITEROOT . 'ext/mime_content_type.func.php');
require_once( SITEROOT . 'class/XMLHelper.php');

session_start();

/*
 * Ce fichier à l'air d'être inclu dans tous les scripts S²low
 * 
 */


//Cette variable est utilisée partout sans être initialisé...
$html = "";