<?php

use Psr\Log\LoggerInterface;
use S2lowLegacy\Class\DatabasePool;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\User;
use S2lowLegacy\Mail\Annuaire;
use S2lowLegacy\Mail\GroupeMail;
use S2lowLegacy\Mail\MailAnnuaire;
use S2lowLegacy\Mail\MailErrors;
use S2lowLegacy\Mail\MailList;
use S2lowLegacy\Mail\MailPeer;
use S2lowLegacy\Mail\MailTransaction;
use S2lowLegacy\Mail\MailUtil;

class MailController
{
    private $lastError;

    public function __construct(
        private readonly MailUtil $mailUtil,
        private readonly User $me,
        private $doc,
    ) {
    }

    public function exitIfNotAdmin()
    {
        if (!$this->me->isAuthorityAdmin()) {
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
            case "list":
                $this->executeList();
                break;
            case "show":
                $this->executeShow();
                break;
            case "SaveError":
                $this->SaveError();
                break;
            case "annuaire":
                $this->exitIfNotAdmin();
                $this->executeAnnuaire();
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
        $search = Helpers:: getVarFromGet("search");
        $deleteId = Helpers:: getVarFromPost("list_id");

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
            $etat = Helpers:: getVarFromGet("etat");

            $tabStatus = MailTransaction::getTabStatus();
            $etat_string = $tabStatus[$etat];

            $sujet = Helpers:: getVarFromGet("sujet");

            try {
                $SendDateFrom = Helpers:: getDateFromGet("SendDateFrom", true);
                $SendDateTo = Helpers:: getDateFromGet("SendDateTo", true);
            } catch (Exception $exception) {
                $this->lastError = $exception->getMessage();
                $_SESSION['last_error'] = $exception->getMessage();
            }

            $cond = " user_id=" . $this->me->getId();

            if ($etat_string) {
                $cond .= " and status='" . $etat_string . "'";
            }
            if ($sujet) {
                $cond .= " and objet ILIKE " . $db->getConnection()->quote('%' . $sujet . '%');
            }
            if ($SendDateFrom) {
                $cond .= " and date_envoi >=" . $db->getConnection()->quote($SendDateFrom);
            }
            if ($SendDateTo) {
                $cond .= " and date_envoi <=" . $db->getConnection()->quote($SendDateTo);
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

    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * \bref envoyer ajouter ou supprimer un contact dans l'annuaire.appelé juste par MailController::run();
     *
     * \param pas de paramètre
     */
    protected function executeAnnuaire()
    {
        $email = Helpers::getVarFromPost("email");
        $description = Helpers::getVarFromPost("description");
        $id = Helpers::getVarFromPost("id");

        if ($email != null) {
            if (!is_valid_email($email)) {
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
        $idArray = Helpers::getVarFromPost("checkbox_id");

        try {
            $groupe_id = Helpers::getIntFromPost("groupe_id", true);
            $old_groupe_id = Helpers::getIntFromPost("old_groupe_id", true);
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
                $groupe_id = Helpers::getIntFromGet("groupe_id", true);
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
        $mailMessageArray = $this->mailUtil->GetMailMessage();
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
}
