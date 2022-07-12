<?php

namespace S2low\Controller;

use Exception;
use Helpers;
use LegacyObjectsManager;
use MailController;
use MailInit;
use Legacy\MailLayout;
use S2low\Services\MailSecurises\MailSecuriseNotification;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

class MailSecuriseController extends AbstractController
{
    private MailLayout $doc;
    /**
     * @var \S2low\Services\MailSecurises\MailSecuriseNotification
     */
    private MailSecuriseNotification $mailSecuriseNotification;

    public function __construct(MailLayout $mailLayout, MailSecuriseNotification $mailSecuriseNotification)
    {
        $this->doc = $mailLayout;
        $this->mailSecuriseNotification = $mailSecuriseNotification;
        LegacyObjectsManager::setLegacyObjectInstancier();
    }

    /**
     * @Route("/modules/mail/index.php", name="app_modules_mail_index")
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function handleRequest(): StreamedResponse
    {
        list($module, $me, $myAuthority) = MailInit::getIdentificationParameters();

//commencer traiter la layout normal correspond de le système.

        $api = Helpers:: getVarFromPost("api");

        $doc = $this->doc;
        if (!$api) {
            $this->doc = new MailLayout("xhtml_mail_ssl.tpl.php");
        }

        //commencer de distribuer des information.
        $command = $_GET["command"] ?? "";

        return new StreamedResponse(
            function () use ($api, $doc, $command, $me, $module, $myAuthority) {
                try {
                    if (!$api) {
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
                    $MailCtl = new MailController($me, $this->doc, $module, $myAuthority, $this->mailSecuriseNotification);
                    $MailCtl->run($command);
                    $doc->closeContent(true);
                    $doc->closeContainer(true);
                    //affichier le pied.
                    $doc->DisplayFoot();
                } catch (Exception $exception) {
                    var_dump($exception->getMessage());
                }
            }
        );
    }
}
