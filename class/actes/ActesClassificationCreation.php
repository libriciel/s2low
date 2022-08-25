<?php

namespace S2lowLegacy\Class\actes;

use ActesClassification;
use ActesEnvelope;
use ActesTransaction;
use S2lowLegacy\Class\Authority;
use S2lowLegacy\Class\DatabasePool;
use S2lowLegacy\Class\Log;
use S2lowLegacy\Class\Module;
use S2lowLegacy\Class\User;
use S2lowLegacy\Class\WorkerScript;

class ActesClassificationCreation
{
    /** @var  Authority */
    private $authority;

    /** @var  User */
    private $user;

    private $frequencyRestriction = true;

    private $lastMessage;
    private $lastTransactionId;

    private $db;

    public function __construct($db = null)
    {
        if (!$db) {
            $db = DatabasePool::getInstance();
        }
        $this->db = $db;
    }

    public function unsetFrequencyRestriction()
    {
        $this->frequencyRestriction = false;
    }

    public function getLastMessage()
    {
        return $this->lastMessage;
    }

    public function getLastTransactionId()
    {
        return $this->lastTransactionId;
    }


    public function sendToAllAuthorities($department = null)
    {
        $this->unsetFrequencyRestriction();

        $authority = new Authority();
        if ($department != null && is_int($department)) {
            if ($department < 100) {
                $department = "0$department";
            }
            $authorities = $authority->getAuthoritiesList("WHERE department='$department'", 10000);
        } else {
            $authorities = $authority->getAllAuthorities();
        }

        /** @var Authority $authority */
        foreach ($authorities as $authority) {
            echo $authority['name'] . " - id " . $authority['id'] . " :";
            if (! $authority['siren']) {
                echo "[PASS] siren absent\n";
                continue;
            }

            $authorityObject = new Authority($authority['id']);
            if (! $authorityObject->isActive()) {
                echo "[PASS] collectivité inactive\n";
                continue;
            }
            if (! $authorityObject->getModulePermByName('actes')) {
                echo "[PASS] module actes inactif\n";
                continue;
            }

            $result = $this->createEnveloppe(new Authority($authority['id']));
            if ($result) {
                echo "[OK]\n";
            } else {
                echo "[FAIL] - " . $this->getLastMessage() . "\n";
            }
        }
    }

    public function createEnveloppe(Authority $authority, User $user = null, $force = false)
    {
        $this->authority = $authority;
        $this->user = $user;
        if (! $user) {
            $result = $this->setDefaultUser();
            if (!$result) {
                $this->lastMessage = "La collectivité ne contient pas d'utilisateur";
                return false;
            }
        }

        if ($this->frequencyRestriction && ActesClassification::hasTodayRequest($this->authority->getId())) {
            $this->lastMessage =  "La dernière demande de classification date de moins d'un jour.";
            return false;
        }

        $env = $this->initEnveloppe();
        $trans = $this->initTransaction($force);

        // Génération du fichier XML de la transaction
        $xml_name = $trans->getStdFileName($env, false);
        if (! $trans->generateMessageXMLFile($xml_name)) {
            $this->lastMessage = "Erreur lors de la génération du message métier :\n" . $trans->getErrorMsg();
            return false;
        }

        $env->addTransaction($trans);


        $actesEnvelopeSerial = new ActesEnvelopeSerialSQL(DatabasePool::getInstance());
        $serialNumber = $actesEnvelopeSerial->getNext($authority->getId());

        // Génération du fichier XML de l'enveloppe
        if (! $env->generateEnvelopeXMLFile($serialNumber)) {
            $this->lastMessage = "Erreur lors de la génération de l'enveloppe.";
            return false;
        }

        // Création de l'archive .tar.gz
        if (! $env->generateArchiveFile()) {
            $this->lastMessage = "Erreur lors de la génération de l'archive.\n" . $env->getErrorMsg();
            return false;
        }

        // Purge des fichiers intermédiaires
        $env->purgeFiles();
        $result = $env->save();
        if (! $result) {
            $this->lastMessage = "Erreur lors de l'enregistrement de l'enveloppe&nbsp;:\n" . $env->getErrorMsg();
            $this->logLastMessage(3);
            return false;
        }

        $trans->set("envelope_id", $env->getId());
        $result = $trans->save();
        if (! $result) {
            $this->lastMessage  = "Erreur lors de l'enregistrement de la transaction&nbsp;:\n" . $trans->getErrorMsg();
            $this->logLastMessage(3);
            $env->deleteArchiveFile();
            $env->delete();
            return false;
        }

        $classifRequest = new ActesClassification();
        $classifRequest->set("requested_by", $this->user->getId());
        $classifRequest->set("request_date", date('Y-m-d H:i:s'));

        if (! $classifRequest->save()) {
            $trans->delete();
            $env->deleteArchiveFile();
            $env->delete();
            $this->lastMessage = "Erreur lors de l'enregistrement de la requête de classification.\n" . $classifRequest->getErrorMsg();
            return false;
        }

        $this->lastMessage = "Création de l'enveloppe n°" . $env->getId() . " contenant une demande de classification. Résultat OK.";
        $this->logLastMessage(1);

        $objectInstancier = \S2lowLegacy\Lib\ObjectInstancierFactory::getObjetInstancier();

        $workerScript = $objectInstancier->get(WorkerScript::class);
        $workerScript->putJobByClassName(ActesAntivirusWorker::class, $trans->getId());

        $this->lastTransactionId = $trans->getId() . "\n";
        return true;
    }

    private function setDefaultUser()
    {
        $sql = "SELECT users.id " .
                " FROM users " .
                " JOIN users_perms ON users.id = users_perms.user_id " .
                " WHERE authority_id=" . $this->authority->getId() .
                " AND status=1 " .
                //" AND role='USER'" .
                " AND module_id =  " . Module::ACTES .
                " AND perm = '" . User::PERM_MODIFICATION . "' " .
                " ORDER BY users.id ASC " .
                " LIMIT 1 " ;
        $id_user = $this->db->getOneValue($sql);
        if (! $id_user) {
            return false;
        }
        $this->user = new User($id_user);
        $this->user->init();
        return true;
    }

    private function getMailRetour()
    {
        $retMail = array(ACTES_TDT_MAIL_ADDRESS);

        if ($this->user->get("email")) {
            $retMail[] = $this->user->get("email");
        }
        if ($this->authority->get("email")) {
            $retMail[] = $this->authority->get("email");
        }
        return $retMail;
    }

    private function getTelephoneContact()
    {
        if ($this->user->get("telephone")) {
            $telephone = $this->user->get("telephone");
        } else {
            $telephone = $this->authority->get("telephone");
        }
        return $telephone;
    }

    private function initEnveloppe()
    {

        $mailRetour = $this->getMailRetour();
        $telephone = $this->getTelephoneContact();

        $env = new ActesEnvelope();

        // Initialisation de l'enveloppe
        $env->set("user_id", $this->user->getId());
        $env->set("siren", $this->authority->get("siren"));
        $env->set("department", $this->authority->get("department"));
        $env->set("district", $this->authority->get("district"));
        $env->set("authority_type_code", $this->authority->get("authority_type_id"));
        $env->set("return_mail", implode('|', $mailRetour));
        $env->set("name", $this->user->getprettyName());
        $env->set("telephone", $telephone);
        $env->set("email", $this->user->get("email"));
        $env->set("file_path", "");
        $env->set("destDir", $this->authority->get("siren") . "/");
        return $env;
    }

    private function initTransaction($force = false)
    {
        $last_classification_date = ActesClassification::getLastRevisionDate($this->authority->getId());
        $trans = new ActesTransaction();
        $trans->set("type", "7");
        if (! $force) {
            $trans->set("last_classification_date", $last_classification_date);
        }
        $trans->set("destDir", $this->authority->get("siren") . "/");
        $trans->set("authority_id", $this->authority->getId());
        $trans->set("user_id", $this->user->getId());
        return $trans;
    }

    private function logLastMessage($severity)
    {
        $result = Log::newEntry(LOG_ISSUER_NAME, $this->lastMessage, $severity, false, 'USER', "actes", $this->user);
        if (! $result) {
            $this->lastMessage .= "\nErreur de journalisation.";
        }
    }
}
