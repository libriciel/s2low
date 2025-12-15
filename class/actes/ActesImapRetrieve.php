<?php

namespace S2lowLegacy\Class\actes;

use Psr\Log\LoggerInterface;
use S2lowLegacy\Class\ImapMailBoxFactory;
use S2lowLegacy\Class\RecoverableException;
use S2lowLegacy\Class\TmpFolder;
use S2lowLegacy\Class\WorkerScript;
use Exception;
use S2lowLegacy\Lib\SigTermHandler;
use S2lowLegacy\Lib\UnrecoverableException;
use PhpImap\Mailbox;

class ActesImapRetrieve
{
    private $actesImapProperties;
    private $actes_response_tmp_local_path;
    private $logger;
    private $imapMailBoxFactory;
    private $sigTermHandler;
    private $workerScript;

    public function __construct(
        ActesImapProperties $actesImapProperties,
        $actes_response_tmp_local_path,
        ImapMailBoxFactory $imapMailBoxFactory,
        LoggerInterface $s2lowLogger,
        SigTermHandler $sigTermHandler,
        WorkerScript $workerScript
    ) {
        $this->actesImapProperties = $actesImapProperties;
        $this->actes_response_tmp_local_path = $actes_response_tmp_local_path;
        $this->imapMailBoxFactory = $imapMailBoxFactory;
        $this->logger = $s2lowLogger;
        $this->sigTermHandler = $sigTermHandler;
        $this->workerScript = $workerScript;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function retrieve()
    {
        $this->logger->info("Debut du script");
        $this->logger->info(
            sprintf(
                "Connexion au serveur IMAP %s:%d%s avec l'utilisateur %s",
                $this->actesImapProperties->host,
                $this->actesImapProperties->port,
                $this->actesImapProperties->imap_options,
                $this->actesImapProperties->login
            )
        );

        $tmpFolder = new TmpFolder();
        $tmp_folder = $tmpFolder->create();

        $mailbox = $this->imapMailBoxFactory->getInstance($this->actesImapProperties, $tmp_folder);
        $mailsIds = $mailbox->searchMailbox('ALL');

        $this->logger->info("Il y a " . count($mailsIds) . " messages dans la boite au lettres");

        foreach ($mailsIds as $mail_id) {
            try {
                $this->saveMail($mailbox, $mail_id);
            } catch (UnrecoverableException $e) {
                throw $e;
            } catch (Exception $e) {
                $this->logger->error("Erreur lors de la sauvegarde de $mail_id : " . $e->getMessage());
                continue;
            }
            $this->logger->info("Suppression du message : $mail_id");
            $mailbox->deleteMail($mail_id);
            if ($this->sigTermHandler->isSigtermCalled()) {
                break;
            }
        }
        $this->logger->info("Expunge de la boite au lettes");
        $mailbox->expungeDeletedMails();
        $tmpFolder->delete($tmp_folder);
        $this->logger->info("Fin du script");
        return true;
    }

    /**
     * @param Mailbox $mailbox
     * @param $mail_id
     * @throws Exception
     * @throws UnrecoverableException
     */
    private function saveMail(Mailbox $mailbox, $mail_id)
    {
        $this->logger->info("Récupération du message : $mail_id");
        $tmp_dir = sys_get_temp_dir() . "/" . date("YmdHis") . "_" . mt_rand(0, mt_getrandmax());

        if (! mkdir($tmp_dir)) {
            $exception_message = "Impossible de créer le répertoire $tmp_dir";
            $this->logger->info($exception_message);
            throw new UnrecoverableException($exception_message);
        }

        $incomingMail = $mailbox->getMail($mail_id);
        $textHtml = $incomingMail->textHtml;

        if (empty($textHtml)) {
            $this->logger->info("Le corps du mail est vide, il ne sera pas sauvegardé");
        } else {
            $message_body_path = $tmp_dir . "/message_body.html";
            $this->logger->info("Sauvegarde du contenu du message HTML $message_body_path");

            $nb_octets = file_put_contents($message_body_path, $textHtml);

            if (! $nb_octets) {
                throw new RecoverableException("Impossible d'enregistrer ou de lire le contenu du mail (message_body)");
            }
        }

        foreach ($incomingMail->getAttachments() as $attachment) {
            $attachment_path = $tmp_dir . '/' . $attachment->name;
            $this->logger->info("Sauvegarde de $attachment_path");

            if (! copy($attachment->filePath, $attachment_path)) {
                $this->logger->error("Impossible de sauvegarder le fichier $attachment_path !");
            }
        }


        $this->logger->info("Déplacement du répertoire $tmp_dir vers {$this->actes_response_tmp_local_path}");

        if (! file_exists($this->actes_response_tmp_local_path)) {
            throw new UnrecoverableException("{$this->actes_response_tmp_local_path} n'existe pas");
        }

        // rename() fonctionne pas si on est sur deux systèmes de fichiers différents... ce qui est le cas sur docker
        $command = "mv $tmp_dir {$this->actes_response_tmp_local_path}";

        exec($command, $output, $return_var);
        if ($return_var != 0) {
            throw new UnrecoverableException("Impossible de déplacer $tmp_dir ");
        }

        $this->workerScript->putJobByClassName(
            ActesAnalyseFichierRecuWorker::class,
            basename($tmp_dir)
        );
    }
}
