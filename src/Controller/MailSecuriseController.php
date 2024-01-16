<?php

namespace S2low\Controller;

use S2lowLegacy\Class\Authority;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\LegacyObjectsManager;
use S2lowLegacy\Class\MailInit;
use Exception;
use MailController;
use Legacy\MailLayout;
use S2low\Services\MailSecurises\MailSecuriseNotification;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

class MailSecuriseController extends AbstractController
{
    private MailLayout $doc;
    /**
     * @var \S2low\Services\MailSecurises\MailSecuriseNotification
     */
    private MailSecuriseNotification $mailSecuriseNotification;
    private Module $module;
    private User $me;
    private Authority $myAuthority;

    public function __construct(MailLayout $mailLayout, MailSecuriseNotification $mailSecuriseNotification)
    {
        $this->doc = $mailLayout;
        $this->mailSecuriseNotification = $mailSecuriseNotification;
        list($this->module, $this->me, $this->myAuthority) = MailInit::getIdentificationParameters();
    }

    /**
     * @Route("/modules/mail/index.php", name="app_modules_mail_index")
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function handleRequest(): StreamedResponse
    {

        //commencer traiter la layout normal correspond de le système.
        $api = Helpers:: getVarFromPost("api");

        $doc = $this->doc;
        if (!$api) {
            $this->doc = new MailLayout("xhtml_mail_ssl.tpl.php");
        }

        //commencer de distribuer des information.
        $command = $_GET["command"] ?? "";
        list($me,$module,$myAuthority) = [$this->me,$this->module,$this->myAuthority];
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

    /**
     * @Route("/modules/mail/api/send-mail.php")
     */
    public function handleApiRequest(): Response
    {
        //Quickfix pour homogénéiser l'utilisation de Helpers::getVarFromRequest
        // On spécifie qu'on utilise bien l'API ...
        $_POST["api"] = 1;

        if (isset($_POST['password'])) {
            $_POST['psw1'] = $_POST['password'];
            $_POST['psw2'] = $_POST['password'];
        }

        $_POST['FileNumber'] = count($_FILES);

        $MailCtl = new MailController($this->me, $this->doc, $this->module, $this->myAuthority, $this->mailSecuriseNotification);
        ob_start();
        $mailId = $MailCtl->executeSend();
        ob_end_clean();

        if ($mailId) {
            return new Response("OK:$mailId\n");
        } else {
            $erreur = $MailCtl->getLastError();
            return new Response("ERROR:$erreur\n");
        }
    }
}
