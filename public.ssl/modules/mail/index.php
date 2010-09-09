<?php
require_once("include/init.php");

require_once("controller/mailController.php");
//commencer traiter la layout normal correspond de le système.
require_once ("lib/MailLayout.class.php");
$doc = new MailLayout();
 
//pour list.php
$doc->addHeader("<script src=\"/javascript/mailList.js\" type=\"text/javascript\"></script>\n");
$doc->addHeader("<script src=\"/javascript/date-picker.js\" type=\"text/javascript\"></script>\n");
$doc->addHeader("<link rel=\"stylesheet\" type=\"text/css\" href=\"/custom/styles/date-picker.css\" />\n");
 
//pour create.php
$doc->addHeader("<script src=\"/javascript/mail.js\" type=\"text/javascript\"></script>\n");


$doc->setTitle(WEBSITE_TITLE);
$doc->buildMenu($me);
$doc->addBody($html);
$doc->DisplayHead();

//commencer de distribuer des information.
$command=$_GET["command"];
$MailCtl=new mailController();
$MailCtl->run($command);



//affichier le pied.
$doc->DisplayFoot();
