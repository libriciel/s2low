<?php

class ActesImapRetrieve {

    private $actesImapProperties;
    private $actes_response_tmp_local_path;
    private $logger;
    private $imapFetchServerFactory;

    public function __construct(
        ActesImapProperties $actesImapProperties,
        $actes_response_tmp_local_path,
        ImapFetchServerFactory $imapFetchServerFactory,
        Logger $logger
    ) {
        $this->actesImapProperties = $actesImapProperties;
        $this->actes_response_tmp_local_path = $actes_response_tmp_local_path;
        $this->imapFetchServerFactory = $imapFetchServerFactory;
        $this->logger = $logger;
    }

    private function log($message){
        $this->logger->log("actes-reception-fichier",$message);
    }

    public function retrieve(){
        $this->log("Debut du script");
        $this->log("Connection au serveur IMAP {$this->actesImapProperties->host}");

        $server = $this->imapFetchServerFactory->getInstance($this->actesImapProperties->host, $this->actesImapProperties->port);
        $server->setAuthentication($this->actesImapProperties->login,$this->actesImapProperties->password);

        $messages = $server->getMessages();
        $this->log("Il y a ".count($messages)." messages dans la boite au lettres");

        foreach($messages as $message){
            $this->saveMail($message);

            $this->log("Suppression du message : ".($message->getOverview()->message_id));
            $message->delete();
            $server->expunge();
        }

        $this->log("Fin du script");
        return true;
    }


    private function saveMail(\Fetch\Message $message){
        $this->log("Récupération du message : ".($message->getOverview()->message_id));

        $path = $this->actes_response_tmp_local_path . "/" . date("YmdHis")."_".mt_rand(0,mt_getrandmax());
        $this->log("Création du répertoire $path");
        if (! mkdir( $path)){
            $exception_message = "Impossible de créer le répertoire $path";
            $this->log($exception_message);
            throw new Exception($exception_message);
        }
        $message_body_path = $path."/message_body.html";
        $this->log("Sauvegarde du contenu du message HTML $message_body_path");
        file_put_contents($message_body_path,$message->getMessageBody(true));

        foreach($message->getAttachments() as $attachment){
            $attachment_path = $path . "/" . $attachment->getFileName();
            $this->log("Sauvegarde de $attachment_path");
            $attachment->saveAs($attachment_path);
            $this->transcode($attachment_path);

        }
    }

    //Je vois vraiment pas pourquoi on doit faire ça
    //Le simulateur Java est buggé : il envoi des fichiers en UTF-8, mais le cartouche <?xml indique ISO-8859-1
    //Peut-être que de la même manière la plateforme DGCL envoi la meme chose ?
    private function transcode($path){
        $out = exec("file -b --mime-encoding $path");
        if (preg_match("#utf-8#",$out)){
            $this->log("utf-8 -> iso-8859-1 : $path");
            $fileout = "/tmp/".date("YmdHis_".mt_rand(0,mt_getrandmax()));
            exec("iconv -f utf-8 -t iso-8859-1 $path > $fileout");
            exec("mv $fileout $path");
        }
    }

}