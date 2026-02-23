<?php

use S2low\Services\MailSecurises\MailSecuriseNotification;
use S2lowLegacy\Class\Authority;
use S2lowLegacy\Class\DatabasePool;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\Log;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;
use S2lowLegacy\Mail\Annuaire;
use S2lowLegacy\Mail\GroupeMail;
use S2lowLegacy\Mail\IMailHeader;
use S2lowLegacy\Mail\MailAnnuaire;
use S2lowLegacy\Mail\MailErrors;
use S2lowLegacy\Mail\MailHeader;
use S2lowLegacy\Mail\MailIncludedFile;
use S2lowLegacy\Mail\MailList;
use S2lowLegacy\Mail\MailMessageEmis;
use S2lowLegacy\Mail\MailPeer;
use S2lowLegacy\Mail\MailTransaction;
use S2lowLegacy\Mail\MailUtil;

class MailController
{
    private $MailMessageEmis = array ();
    private $MailAnnuaireArray = array();

    private $lastError;

    public function __construct(
        private readonly User $me,
        private $doc,
        private readonly Module $module,
        private readonly Authority $myAuthority,
        private readonly MailSecuriseNotification $mailSecuriseNotification,
    ) {
    }

    public function exitIfNotAdmin()
    {
        if (! $this->me->isAuthorityAdmin()) {
            exit;
        }
    }

  /**
   * \bref dispatch le message.
   *
   * \param char $action
   *
   */
    public function run($action)
    {

        // action will be called in index.php,initialize by diffrent type of action.
        switch ($action) {
            case "create":
                $this->executeCreate();
                break;
            case "list":
                $this->executeList();
                break;
            case "show":
                $this->executeShow();
                break;
            case "send":
                $this->executeSendAndDisplayResult();
                break;
            case "SaveError":
                $this->SaveError();
                break;
            case "annuaire":
                $this->exitIfNotAdmin();
                $this->executeAnnuaire();
                break;
            case "savenewemail":
                $this->executeSaveNewEmail();
                break;
            default:
                $this->executeList();
        }
    }

/**
 * \bref list les email reçu.
 * \bref appelé just par mailctroller::run();
 * \param: pas de parametre
 */
    protected function executeList()
    {
        $etat = "";
        $sujet = "";
        $SendDateFrom = "";
        $SendDateTo = "";
        $search = Helpers :: getVarFromGet("search");
        $deleteId = Helpers :: getVarFromPost("list_id");

     //---delete l'enregistment choisi.
     //FIXME : ca n'a rien à foutre là: faire un script intermédiaire
        $db = DatabasePool::getInstance();
        $mailList = new MailList($db, $this->me->getId());

        if ($deleteId != null) {
            foreach ($deleteId as $transId) {
                $detail = $mailList->getDetail($transId);
                if ($detail) {
                    MailPeer::DeleteMailTransation($transId);
                } else {
                    $_SESSION['last_error'] = "ERROR: cette transaction n'existe pas";
                }
            }
        }
     //----delete fini

     //contruit la filtre sql requete.
        $MailTransaction = new MailTransaction();
        if (!$search) {
            $MailTransactions = MailPeer::mailList($MailTransaction, $this->me->getId());
        } else {
            $etat = Helpers :: getVarFromGet("etat");

            $tabStatus = MailTransaction::getTabStatus();
            $etat_string = $tabStatus[$etat];

            $sujet = Helpers :: getVarFromGet("sujet");

            try {
                $SendDateFrom = Helpers :: getDateFromGet("SendDateFrom", true);
                $SendDateTo = Helpers :: getDateFromGet("SendDateTo", true);
            } catch (Exception $exception) {
                $this->lastError = $exception->getMessage();
                $_SESSION['last_error'] = $exception->getMessage();
            }

            $cond = " user_id=" . $this->me->getId();

            if ($etat_string) {
                $cond .= " and status='" . $etat_string . "'";
            }
            if ($sujet) {
                $cond .= " and objet ILIKE " . $db->getPDO()->quote('%' . $sujet . '%');
            }
            if ($SendDateFrom) {
                $cond .= " and date_envoi >=" . $db->getPDO()->quote($SendDateFrom);
            }
            if ($SendDateTo) {
                $cond .= " and date_envoi <=" . $db->getPDO()->quote($SendDateTo);
            }


            $MailTransactions = MailPeer::mailSearch($MailTransaction, $cond);
        }

        # 🤮 VERY VERY UGLY 🤮
        # Needed by the pager
        $_SERVER['PHP_SELF'] = '/modules/mail/index.php';

        $this->doc->buildPager($MailTransaction, true);
        $this->doc->closeSideBar(true);
        $this->doc->openContent(true);
        include __DIR__ . "/../template/list.php";
    }

  /**
   *
   *
   * FIXME FIXME
   *
   * passer par un script intermédiaire
   *
   * FIXME FIXME
   *
   *
 * \bref créer un nouvel email.
 * \bref appelé just par mailctroller::run();
 * \param: pas de parametre
 */
    protected function executeCreate()
    {
     //traitement des information
        //fini de la tratement
        //affichier la page
        $this->doc->closeSideBar(true);
        $this->doc->openContent(true);
        include(__DIR__ . "/../template/create.php");
    }

  /**
 * \bref afficher le détail d'un email.
 * \bref appelé juste par MailController::run();
 * \param: pas de paramètre
 */
    protected function executeShow()
    {
        $error = $this->SaveError();
     //traitement des information
        try {
            $trans_id = Helpers::getIntFromGet("trans_id");
        } catch (Exception $e) {
            echo $e->getMessage();
            return false;
        }
        $mailTransaction = new MailTransaction($trans_id);
        $mailTransaction->init();
        $fndownload = $mailTransaction->getFNDownload();

        $mailEmisArray = MailPeer::GetMailEmis($trans_id);
     //print_r($mailEmisArray);
        if ($mailEmisArray == null) {
              echo "Le mail n'existe pas.";
              return false;
        }
        $mailErrors = MailPeer::GetMailErrors($trans_id);
        $mailIncludeFileArray = MailPeer::GetIncludeFiles($trans_id);
    //fini de la tratement
     //affichier la page
        $this->doc->closeSideBar(true);
        $this->doc->openContent(true);
        include __DIR__ . "/../template/show.php";
    }

    //HACK béquille pour transformer les mails ...
    public function explodeMail($mail)
    {
        $result = array();
        $lesMails = explode(",", $mail);
        foreach ($lesMails as $un_mail) {
            $un_mail = trim($un_mail);
            if (! $un_mail) {
                continue;
            }
            $matches  = array();
            if (preg_match('/(.*) \(groupe\)/', $un_mail, $matches)) {
                $groupe_name = $matches[1];
                $groupeMail = new GroupeMail();
                $groupe_id = $groupeMail->getGroupeIdFromName($groupe_name, $this->me->get('authority_id'));
                if ($groupe_id) {
                    $annuaire = MailPeer::GetAnnuaire($this->me->get('authority_id'), $groupe_id);
                    foreach ($annuaire as $personne) {
                        if ($personne['description']) {
                            $result[] = '"' . $personne['description'] . '" <' . $personne['mail_address'] . '>';
                        } else {
                            $result[] = $personne['mail_address'];
                        }
                    }
                }
            } else {
                $un_mail = str_replace("[", "<", $un_mail);
                $un_mail = str_replace("]", ">", $un_mail);
                $result[] = $un_mail;
            }
        }
        $newresult = array();
        foreach ($result as $chaine) {
            if (!in_array($chaine, $newresult)) {
                $newresult[] = $chaine;
            }
        }
        return implode(",", $newresult);
    }

    public function getLastError()
    {
        return $this->lastError;
    }


    public function executeSendAndDisplayResult()
    {
        $result = $this->executeSend();

        if (! $result) {
            $returnMsg = $this->getLastError();
            ;
                        $this->doc->closeSideBar(true);
                        $this->doc->openContent(true);
            include __DIR__ . "/../template/sendfailed.php";
        } else {
                    $this->doc->closeSideBar(true);
                    $this->doc->openContent(true);
                    include __DIR__ . "/../template/send.php";
        }
    }

    public function logError()
    {
        $result = Log :: newEntry(LOG_ISSUER_NAME, $this->lastError, 3, false, 'USER', $this->module->get("name"), $this->me);
        if (! $result) {
            $this->lastError .= "\nErreur de journalisation.";
        }
    }


    public function executeSend()
    {
        //FIXME fonction trop grande ...


        require_once(dirname(__FILE__) . "/../lib/mailfunction.php");

        //HACK
        if (empty($_POST) && empty($_FILES)) {
            $this->lastError = "Les pièces jointes sont trop volumineuses (80 Mo maximum)" ;
            return false;
        }

        //vérification de mail adress.
        $mailTo = Helpers :: getVarFromPost("mailto");
        $mailCC = Helpers :: getVarFromPost("mailcc");
        $mailBCC = Helpers :: getVarFromPost("mailcci");


        $mailTo = $this->explodeMail($mailTo);
        $mailCC = $this->explodeMail($mailCC);
        $mailBCC = $this->explodeMail($mailBCC);

        $subject = Helpers :: getVarFromPost("objet");
        $message = Helpers :: getVarFromPost("message");
        $message = str_replace("\r", "", $message);
        $send_password = Helpers :: getVarFromPost("send_password") ?? false;

        if (! $mailTo) {
            $this->lastError = "Le destinataire est obligatoire";
            return false;
        }

        if (checkAllEmail($mailTo) == false) {
            $this->lastError = "L'adresse email est incorrecte ! mailto=$mailTo ";
            return false;
        }

        if (! $subject) {
            $this->lastError = "L'objet du mail est obligatoire";
            return false;
        }

        if (! $message) {
            $this->lastError =  "Le corps du message ne peut pas être vide";
            return false;
        }

        if (mb_strlen($message) > 2000) {
            $this->lastError =  "Le corps du message ne peut dépasser les 2000 caractères : " . mb_strlen($message) . " caractères trouvés.";
            return false;
        }
        if ($mailCC && ! checkAllEmail($mailCC)) {
            $this->lastError =  "mailCC: Adresse email incorrecte !";
            return false;
        }

        if ($mailBCC && ! checkAllEmail($mailBCC)) {
            $this->lastError =  "mailBCC: Adresse email incorrecte !";
            return false;
        }

        //-------fini de la vérification
        //----ini mail tranaction.
        // mail transaction faut absolutment inite avant tous les autre opération car tous les autre tableau need
        // mail transaction id.
        $mailTransaction = new MailTransaction();
        $mailTransaction->newSave($this->me->getId());
        $Transaction_id = $mailTransaction->getId();
        $mailIncludedFiles = array();

        //--------------------------------------------------------------------
        //FileNumber = le nombre de File est attaché. Il commence par 1.
        //Il est défini dans le fichier de javascript file: mail.js
        $InputFileName = array();
        $FileNumber = Helpers :: getVarFromPost("FileNumber");
        if ($FileNumber != null) {
            for ($i = 1; $i <= $FileNumber; $i++) {
               // le nom de uploadFile pass par var _FILES
               // le nom de chaque file =uploadFile1, uploadFile2,,,,jusqu'à FileNumber
               // parcque des fois les utilisateur supprime une fichier qu'il a déjas ajouté et le FileNumber va pas diminuer enmeme temp
               // donc il y aura de trou entre les nombre.
                if (defined('MAIL_DEBUG')) {
                      echo "filenumber=" . $i;
                      hecho("filename=" . $_FILES['uploadFile' . $i]['name']);
                }
                if (!  empty($_FILES['uploadFile' . $i]['name'])) {
                     $InputFileName[] = 'uploadFile' . $i;
                }
            }
        }
         //----------------------

        $mailHeader = \S2lowLegacy\Lib\ObjectInstancierFactory::getObjetInstancier()->get(IMailHeader::class);

        $mailHeader->setAuthorityName($this->myAuthority->get('name'));
        $mailHeader->setFromMail($this->myAuthority->get('email_mail_securise'));
        $mailHeader->setFromDescription($this->myAuthority->get('descr_mail_securise'));

        $mailUtil = new MailUtil($mailHeader);

        if (count($InputFileName) > 0) {
            $now = date("Y-m-d H:i:s");
            $mailTransaction->set("fn_download", md5("mail" . $now) . mt_rand(0, mt_getrandmax()));
            $mailTransaction->save(false);

            // créer un repertoir de md5
            $newdir = MAIL_FILES_UPLOAD_ROOT . "/" . $mailTransaction->getFNDownload() . '/';
            if (!mkdir($newdir, 0755, true)) {
                $this->lastError = "La création de répertoire a echoué.";
                $this->logError();
                return false;
            }
            $mailFiles = array();
            foreach ($InputFileName as $Filename) {
                 $temp = new MailIncludedFile();
                if ($temp->newSave($Filename, $Transaction_id, $newdir)) {
                    $mailIncludedFiles[] = $temp;
                } else {
                    $this->lastError = "Le chargement du fichier sur le server a échoué : " . $temp->getLastError();
                    $this->logError();
                    return false;
                }
            }
            foreach ($mailIncludedFiles as $mailIncludeFile) {
                $mailFiles[] = $newdir . $mailIncludeFile->getFileName();
            }
            $Zipfile = $newdir . "mail.zip";
            if (!$mailUtil->zip($mailFiles, $Zipfile)) {
                $this->lastError = $mailUtil->errorMsg;
                $this->logError();
                return false;
            }
            foreach ($mailIncludedFiles as $mailIncludeFile) {
                unlink($newdir . $mailIncludeFile->getFileName());
            }
        } else {
            $mailTransaction->set("fn_download", null);
            $mailTransaction->save(false);
        }

        $this->SaveMailEmis($mailTo, $Transaction_id, "mailTo");
        $this->SaveMailEmis($mailCC, $Transaction_id, "mailCC");

        $this->SaveMailEmis($mailBCC, $Transaction_id, "mailBCC");

        if (
            !$this->mailSecuriseNotification->send(
                $this->MailMessageEmis,
                $mailTransaction->getPassword(),
                $mailHeader,
                $send_password === "on"
            )
        ) {
            $this->lastError = "Échec lors de l'envoi.";
            $this->logError();
            //traiter les messages d'échec.
            $mailTransaction->delete();
            foreach ($this->MailMessageEmis as $mailEmis) {
                $mailEmis->delete();
            }
            foreach ($mailIncludedFiles as $file) {
                $file->delete();
            }
            return false;
        }
        $msg = "Envoi de mail réussi.";
        if (!Log :: newEntry(LOG_ISSUER_NAME, $msg, 1, false, 'USER', $this->module->get("name"), $this->me)) {
            $this->lastError = "\nErreur de journalisation.";

            return false;
        }

        return $mailTransaction->getID();
    }

    /**
     * \bref envoyer ajouter ou supprimer un contact dans l'annuaire.appelé juste par MailController::run();
     *
     * \param pas de paramètre
     */
    protected function executeAnnuaire()
    {
        $email = Helpers :: getVarFromPost("email");
        $description = Helpers :: getVarFromPost("description");
        $id = Helpers :: getVarFromPost("id");

        if ($email != null) {
            if (! is_valid_email($email)) {
                $_SESSION['last_message'] = "L'email n'est pas valide";
            } else {
                $annuaire = new MailAnnuaire();
                $annuaire->set("mail_address", $email);
                $annuaire->set("description", $description);
                $annuaire->set("authority_id", $this->me->get('authority_id'));
                $annuaire->set("id", $id);
                $annuaire->save(false);
            }
        }
        $idArray = Helpers :: getVarFromPost("checkbox_id");

        try {
            $groupe_id = Helpers :: getIntFromPost("groupe_id", true);
            $old_groupe_id = Helpers :: getIntFromPost("old_groupe_id", true);
        } catch (Exception $exception) {
            $this->lastError = $exception->getMessage();
            return false;
        }
        $groupe = new GroupeMail($groupe_id);

        if ($idArray != null) {
            foreach ($idArray as $id) {
                if ($groupe_id) {
                    if ($old_groupe_id == $groupe_id) {
                        $groupe->removeUser($id);
                    } else {
                        $groupe->addUser($id);
                    }
                } else {
                    if ($groupe->isUserInGroup($id)) {
                        $this->lastError = "Impossible de supprimer un utilisateur qui est encore dans un groupe";
                    } else {
                        $annuaire = new MailAnnuaire($id);
                        $annuaire->delete();
                    }
                }
            }
            unset($groupe_id);
            if ($this->lastError) {
                $_SESSION['last_error'] = $this->lastError;
            } else {
                $_SESSION['last_message'] = "Opération effectuée avec succés";
            }
        }

        if ($old_groupe_id) {
            $groupe_id = $old_groupe_id;
        } else {
            try {
                $groupe_id = Helpers :: getIntFromGet("groupe_id", true);
            } catch (Exception $e) {
                echo $e->getMessage();
                return false;
            }
        }

        $mailAnnuaireArray = MailPeer::GetAnnuaire($this->me->get('authority_id'), $groupe_id);
        $groupe = new GroupeMail();
        $groupeArray = $groupe->getGroupeByAuthorityId($this->me->get('authority_id'));
        $bd = DatabasePool::getInstance();
        $annuaire = new Annuaire($bd, $this->me->get('authority_id'));

        if (!is_null($groupe_id) && !in_array($groupe_id, array_keys($groupeArray))) {
            $_SESSION['last_error'] = "Le group_id '$groupe_id' n'existe pas.";
            $groupe_id = null;
        }

        if ($groupe_id) {
            foreach ($groupeArray as $groupe) {
                if ($groupe['id'] == $groupe_id) {
                    $groupe_name = $groupe['name'];
                    break;
                }
            }
        }
                $this->doc->closeSideBar(true);
                $this->doc->openContent(true);

        include __DIR__ . "/../template/annuaire.php";
    }


/**
 * \bref: examiner la boit au lettre de tedetis,
 * \bref        récupérer les nouveau email
 * \bref        trouver le quelle mail n'est pas réussi d'envoyeer
 * \bref        sauvegarder dans la base de donnee
 * \bref    appelé just par mailctroller::show();
 * \param: pas de parametre
 */
    protected function SaveError()
    {
        $mailUtil = new MailUtil();
        $mailMessageArray = $mailUtil->GetMailMessage();
        if ($mailMessageArray == null) {
          //echo "mail emis failed.";
            return false;
        }
        $length = sizeof($mailMessageArray["mail_emis_id"]);
        for ($i = 0; $i < $length; $i++) {
            $mailErros = new MailErrors();
            $mailErros->set("mail_message_emis_id", $mailMessageArray["mail_emis_id"][$i]);
            $mailErros->set("message_retour", $mailMessageArray["body"][$i]);
            $now = date("Y-m-d H:i:s");
            $mailErros->set("date_registered", $now);
            $mailErros->save(false);
        }
        return true;
    }

/**
 * \bref save les mail emis dans tableau mail_emis
 * \bref    appelé just par mailctroller::run();
 * \param: pas de parametre
 */
    protected function SaveMailEmis($mail, $Transaction_id, $type)
    {
        if ($mail == null) {
            return false;
        }
       //supprime le vircule a la fin.
       // le séparateur  is vircule
        $Emails = explode(",", $mail);
        foreach ($Emails as $Email) {
            $Email = str_replace("[", "<", $Email);
            $Email = str_replace("]", ">", $Email);
            $Email = trim($Email);

            if ($Email != "") {
                 $this->MailMessageEmis[] = new MailMessageEmis();
                if (end($this->MailMessageEmis)->newSave($Email, $Transaction_id, $type) == false) {
                    return false;
                }

               //vérifier le mail adress exist déjas ou pas
               //si non; met dans MailAnnuaireArray pour traiter aprés.
                if (MailPeer::VerifierMailAnnuaire($Email, $this->me->get('authority_id')) == false) {
                    $this->MailAnnuaireArray[] = $Email;
                }
            }
        }
        return true;
    }

    protected function executeSaveNewEmail()
    {
        $emails = Helpers :: getVarFromPost("newMailAddress");
        $descriptions = Helpers :: getVarFromPost("newMailDescription");
        $maxLengh = count($emails);

        echo $maxLengh;
        for ($i = 0; $i < $maxLengh; $i++) {
            $annuaire = new MailAnnuaire();
            $annuaire->set("user_id", $this->me->getId());
            $annuaire->set("mail_address", $emails[$i]);
            $annuaire->set("description", $descriptions[$i]);
            $annuaire->save(false);
        }
        $this->doc->closeSideBar(true);
        $this->doc->openContent(true);
        include __DIR__ . "/../template/newemail.php";
    }
}
