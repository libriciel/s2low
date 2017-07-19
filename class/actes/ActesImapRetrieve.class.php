<?php

class ActesImapRetrieve {

    private $actesImapProperties;
    private $actes_response_tmp_local_path;
    private $imapWrapper;
    private $logger;

    public function __construct(
        ActesImapProperties $actesImapProperties,
        $actes_response_tmp_local_path,
        ImapWrapper $imapWrapper,
        Logger $logger
    ) {
        $this->actesImapProperties = $actesImapProperties;
        $this->actes_response_tmp_local_path = $actesImapProperties;
        $this->imapWrapper = $imapWrapper;
        $this->logger = $logger;
    }
    private function log($message){
        $this->logger->log("actes-reception-fichier",$message);
    }
    public function retrieve(){
        $this->log("Debut du script");
        $this->log("Connection au serveur IMAP {$this->actesImapProperties->host}");
        $this->imapWrapper->setLogin($this->actesImapProperties->login,$this->actesImapProperties->password);
        $this->imapWrapper->setServer($this->actesImapProperties->host);
        $this->imapWrapper->setPort($this->actesImapProperties->port);
        $this->imapWrapper->setOption("novalidate-cert");
        $this->imapWrapper->open();

        $nb_mail =  $this->imapWrapper->getNbMessage();
        $this->log("Il y a $nb_mail mails disponibles");

        $overview = $this->imapWrapper->mailboxStatus();

        $overview = $this->imapWrapper->fetchOverview(1,$nb_mail);

        print_r($overview);
        foreach($overview as $mail){
            $structure = $this->imapWrapper->getMessageStructure($mail->uid);
            $this->parcourPart($structure);

        }


        //recup des mails
        //
        $this->log("Fin du script");
    }


    private function parcourPart($structure){
        foreach($structure->parts as $part){
            print_r($part);
            //$this->imapWrapper->getBodyPart(($uid,$part);
            if (isset($part->parts)){
                $this->parcourPart($part);
            }
        }
    }

}