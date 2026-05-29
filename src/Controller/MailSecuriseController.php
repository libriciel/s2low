<?php

namespace S2low\Controller;

use Exception;
use MailController;
use S2lowLegacy\Class\Authority;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\MailInit;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;
use S2lowLegacy\Mail\MailLayout;
use S2lowLegacy\Mail\MailUtil;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

class MailSecuriseController extends AbstractController
{
    private Module $module;
    private User $me;
    private Authority $myAuthority;

    public function __construct(
        private readonly MailUtil $mailUtil,
        private MailLayout $doc,
    ) {
        list($this->module, $this->me, $this->myAuthority) = MailInit::getIdentificationParameters();
    }

    #[Route(
        path: '/modules/mail/index.php',
        name: 'app_modules_mail_index',
    )]
    public function index(): StreamedResponse
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
                        $doc->addHeader('<script type="text/javascript" src="' . Helpers::getLink("/jsmodules/jquery.js") . '"></script>');
                        $doc->addHeader('<script type="text/javascript" src="' . Helpers::getLink("/jsmodules/jqueryui.js") . '"></script>');

                        //pour create.php
                        $doc->addHeader("<script src=\"/javascript/mail.js\" type=\"text/javascript\"></script>\n");


                        $doc->setTitle(WEBSITE_TITLE);
                        $doc->openContainer();
                        $doc->openSideBar();
                        $doc->buildMenu($me);

                        $doc->DisplayHead();
                    }
                    $MailCtl = new MailController($this->mailUtil, $me, $this->doc);
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
