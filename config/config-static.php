<?php
/////////////////////////////////////////////
// En principe ne pas modifier ci-dessous  //
/////////////////////////////////////////////
// Emplacement des templates HTML
define('HTML_TEMPLATE_PATH', SITEROOT . '/public/custom/templates');
// Template par défaut
define('DEFAULT_HTML_TEMPLATE', 'generic.tpl.php');

// Nom de l'application lors de la génération d'entrée de journal
define('LOG_ISSUER_NAME', 'Interface Web');

// Version et date de l'application
define('PRODUCT_RELEASE', '1.1');
define('PRODUCT_RELEASE_DATE', '28/10/2009');

//////////////////////////////////
///// Paramètre module Mail  /////
//////////////////////////////////

define('MAIL_SITEROOT', SITEROOT . '/public.ssl/modules/mail');
//define('MAIL_DEBUG','mail_debug');
//define('MAIL_PULIC',SITEROOT.'public/modules/mail');
define('MAIL_HTML', MAIL_SITEROOT . '/html');
