<?php
/////////////////////////////////////////////
// En principe ne pas modifier ci-dessous  //
/////////////////////////////////////////////

define('SITEROOT', __DIR__ . "/../");


// Emplacement des templates HTML
define('HTML_TEMPLATE_PATH', SITEROOT . '/public/custom/templates');
// Template par défaut
define('DEFAULT_HTML_TEMPLATE', 'generic.tpl.php');

// Nom de l'application lors de la génération d'entrée de journal
define('LOG_ISSUER_NAME', 'Interface Web');

//////////////////////////////////
///// Paramètre module Mail  /////
//////////////////////////////////

define('MAIL_SITEROOT', SITEROOT . '/public.ssl/modules/mail');
//define('MAIL_DEBUG','mail_debug');
//define('MAIL_PULIC',SITEROOT.'public/modules/mail');
define('MAIL_HTML', MAIL_SITEROOT . '/html');


//Emplacement du schéma des PES V2
//define("HELIOS_XSD_PATH",__DIR__."/../xsd/Schemas_PES_v471_072015/");
//define("HELIOS_XSD_PATH",__DIR__."/../xsd/Schemas_PES_v491_18042016/");
//define("HELIOS_XSD_PATH",__DIR__."/../xsd/Schemas_PES 5.3/");
//define("HELIOS_XSD_PATH",__DIR__."/../xsd/schemas_pes_5.5_301017/");
//define("HELIOS_XSD_PATH",__DIR__."/../xsd/schemas_pes_v5.7_072018/");
define("HELIOS_XSD_PATH",__DIR__."/../xsd/schemas_pes_v5.11/");
