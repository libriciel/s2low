<?php
list($doc, $mailSecuriseNotification) = LegacyObjectsManager::getLegacyObjectInstancier()
    ->getArray([MailLayout::class, \S2low\Services\MailSecurises\MailSecuriseNotification::class]);
list($module, $me, $myAuthority) = MailInit::getIdentificationParameters();

//commencer traiter la layout normal correspond de le système.

$api = Helpers :: getVarFromPost("api");

if (! $api) {
    $doc = new MailLayout("xhtml_mail_ssl.tpl.php");

//pour list.php
    $doc->addHeader("<script src=\"/javascript/mailList.js\" type=\"text/javascript\"></script>\n");
    $doc->addHeader("<script src=\"/javascript/date-picker.js\" type=\"text/javascript\"></script>\n");
    $doc->addHeader("<link rel=\"stylesheet\" type=\"text/css\" href=\"/custom/styles/date-picker.css\" />\n");

//pour create.php
    $doc->addHeader("<script src=\"/javascript/mail.js\" type=\"text/javascript\"></script>\n");


    $doc->setTitle(WEBSITE_TITLE);
    $doc->openContainer();
    $doc->openSideBar();
    $doc->buildMenu($me);

    $doc->DisplayHead();
}
//commencer de distribuer des information.
if (isset($_GET["command"])) {
    $command = $_GET["command"];
} else {
    $command = "";
}
$MailCtl = new MailController($me, $doc, $module, $myAuthority, $mailSecuriseNotification);
$MailCtl->run($command);
$doc->closeContent(true);
$doc->closeContainer(true);
//affichier le pied.
$doc->DisplayFoot();
