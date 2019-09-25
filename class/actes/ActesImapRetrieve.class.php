<?php

use PhpImap\Mailbox;

class ActesImapRetrieve {

    private $actesImapProperties;
    private $actes_response_tmp_local_path;
    private $logger;
    private $imapMailBoxFactory;

    public function __construct(
        ActesImapProperties $actesImapProperties,
        $actes_response_tmp_local_path,
		ImapMailBoxFactory $imapMailBoxFactory,
        S2lowLogger $s2lowLogger
    ) {
        $this->actesImapProperties = $actesImapProperties;
        $this->actes_response_tmp_local_path = $actes_response_tmp_local_path;
        $this->imapMailBoxFactory = $imapMailBoxFactory;
        $this->logger = $s2lowLogger;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function retrieve(){
        $this->logger->info("Debut du script");
		$this->logger->info("Connection au serveur IMAP {$this->actesImapProperties->host}");

        $tmpFolder = new TmpFolder();
        $tmp_folder = $tmpFolder->create();

		$mailbox = $this->imapMailBoxFactory->getInstance($this->actesImapProperties,$tmp_folder);
		$mailsIds = $mailbox->searchMailbox('ALL');

		$this->logger->info("Il y a ".count($mailsIds)." messages dans la boite au lettres");
        $sigtermHandler = new SigTermHandler();
        foreach($mailsIds as $mail_id){
            try {
                $this->saveMail($mailbox, $mail_id);
            } catch (UnrecoverableException $e){
                throw $e;
            } catch (Exception $e){
				$this->logger->error("Erreur lors de la sauvegarde de $mail_id");
                continue;
            }
			$this->logger->info("Suppression du message : $mail_id");
			$mailbox->deleteMail($mail_id);
            if ($sigtermHandler->isSigtermCalled()){
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
    private function saveMail(Mailbox $mailbox,$mail_id){
		$this->logger->info("Récupération du message : $mail_id");
		$tmp_file = sys_get_temp_dir()."/".date("YmdHis")."_".mt_rand(0,mt_getrandmax());

        if (! mkdir( $tmp_file)){
            $exception_message = "Impossible de créer le répertoire $tmp_file";
			$this->logger->info($exception_message);
            throw new UnrecoverableException($exception_message);
        }

        $message_body_path = $tmp_file."/message_body.html";
		$this->logger->info("Sauvegarde du contenu du message HTML $message_body_path");


        $incomingMail = $mailbox->getMail($mail_id);

        $nb_octets = file_put_contents($message_body_path,$incomingMail->textHtml);

        if (! $nb_octets){
        	throw new RecoverableException("Impossible d'enregistrer ou de lire le contenu du mail (message_body)");
		}

		foreach ($incomingMail->getAttachments() as $attachment) {

			$attachment_path = $tmp_file . "/" . $attachment->name;
			$this->logger->info("Sauvegarde de $attachment_path");

			if (! copy($attachment->filePath,$attachment_path)){
				$this->logger->error("Impossible de sauvegarder le fichier $attachment_path !");
				continue;
			}
			$this->transcode($attachment_path);
		}


		$this->logger->info("Déplacement du répertoire $tmp_file vers {$this->actes_response_tmp_local_path}");

        if (! file_exists($this->actes_response_tmp_local_path)){
        	throw new UnrecoverableException("{$this->actes_response_tmp_local_path} n'existe pas");
		}

        // rename() fonctionne pas si on est sur deux systèmes de fichiers différents... ce qui est le cas sur docker
		$command = "mv $tmp_file {$this->actes_response_tmp_local_path}";

		exec($command,$output,$return_var);
        if ($return_var != 0){
        	throw new UnrecoverableException("Impossible de déplacer $tmp_file ");
		}
    }

    //Je vois vraiment pas pourquoi on doit faire ça
    //Le simulateur Java est buggé : il envoi des fichiers en UTF-8, mais le cartouche <?xml indique ISO-8859-1
    //Peut-être que de la même manière la plateforme DGCL envoi la meme chose ?
    private function transcode($path){
        $out = exec("file -b --mime-encoding $path");
        if (preg_match("#utf-8#",$out)){
			$this->logger->info("utf-8 -> iso-8859-1 : $path");
            $fileout = "/tmp/".date("YmdHis_".mt_rand(0,mt_getrandmax()));
            exec("iconv -f utf-8 -t iso-8859-1 $path > $fileout");
            exec("mv $fileout $path");
        }
    }

}