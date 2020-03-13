<?php

class DataActesPdf{

    private $actesTransaction;

    /**
     * @return string
     */
    public function getTexteCollectivite(): string
    {
        return $this->texteCollectivite;
    }

    /**
     * @return string
     */
    public function getTexteUtilisateur(): string
    {
        return $this->texteUtilisateur;
    }

    /**
     * @return mixed
     */
    public function getContenuTableau()
    {
        return $this->contenuTableau;
    }

    /** @var User */
    private $user;
    /** @var string  */
    private $texteCollectivite;
    /** @var string  */
    private $texteUtilisateur;
    /** @var array */
    private $contenuTableau;
    /** @var array */
    private $fichier_table;

    /** @var bool */
    private $addEmailNotificationField;

    /**
     * @param bool $addEmailNotificationField
     */
    public function setAddEmailNotificationField(bool $addEmailNotificationField): void
    {
        $this->addEmailNotificationField = $addEmailNotificationField;
    }

    /**
     * @return mixed
     */
    public function getFichierTable()
    {
        return $this->fichier_table;
    }

    /**
     * @return mixed
     */
    public function getCycleTable()
    {
        return $this->cycle_table;
    }
    private $cycle_table;


    public function __construct($transaction_id)
    {
        $this->actesTransaction = new ActesTransaction();
        $this->actesTransaction->setId($transaction_id);
        $this->actesTransaction->init();

        $envelope = new ActesEnvelope($this->actesTransaction->get("envelope_id"));
        $envelope->init();

        $this->user = new User($envelope->get("user_id"));
        $this->user->init();

        $author = new Authority($this->user->get("authority_id"));
        $author->init();

        $this->texteCollectivite = "Collectivité : ".$author->get("name");
        $this->texteUtilisateur = "Utilisateur : ".$this->user->get("name")." ".$this->user->get("givenname");

        $this->initDataTable();
        $this->initDatafichier_table();
        $this->initcycle_table();
    }

    private function getNotifieA(ActesTransaction $trans){
        if ($trans->get("broadcasted") == 't' ) {
            return "Notifiée à " . $trans->get("broadcast_emails");
        }
        if ($this->addEmailNotificationField && $trans->get("broadcast_emails")){
            return "Notifiée à " . $trans->get("broadcast_emails");
        }
        return "Non notifiée";
    }

    public function initDataTable(){
        //traiter des requêtes
        $transactionTypes = $this->actesTransaction->get("transactionTypes");
        $transNatures = ActesTransaction :: getTransactionNaturesIdDescr();

        if(isset($transNatures[$this->actesTransaction->get("nature_code")])){
            $nature_description = $transNatures[$this->actesTransaction->get("nature_code")];
        } else {
            $nature_description = "n/a";
        }

        $notification = $this->getNotifieA($this->actesTransaction);

        $classifcation = $this->actesTransaction->get("classification") ;
        $classifcation_string = $this->actesTransaction->get('classification_string');
        if ($classifcation_string) {
            $classifcation .= " - $classifcation_string";
        }

        $arch_url = $this->actesTransaction->get("archive_url");
        if (empty($arch_url))
            $arch_url= "Non définie";

        $this->contenuTableau =[
            ["Type de transaction:",$transactionTypes[$this->actesTransaction->get("type")]],
            ["Nature de l'acte:",$nature_description],
            ["Numéro de l'acte:",$this->actesTransaction->get("number")],
            ["Date de la décision:",$this->actesTransaction->get("decision_date")],
            ["Objet:",$this->actesTransaction->get("subject")],
            ["Documents papiers complémentaires:",$this->actesTransaction->getDocumentPapier()?"OUI":"NON"],
            ["Classification matières/sous-matières:",$classifcation],
            ["Identifiant unique:",$this->actesTransaction->get("unique_id")],
            ["URL d'archivage:",$arch_url],
            ["Notification:",$notification]
        ];
    }

    public function initDatafichier_table(){
        $files = $this->actesTransaction->fetchFilesList();

        foreach ($files as $file) {
            $fileTemp=[];
            $written = false;
            if ($file["posted_filename"])
            {

                $fileTemp[]=["Nom original :",$file["posted_filename"],$file["mimetype"],$file["size"]];
                $written = true;

            }
            if ($file["name"])
            {
                list($size,$mimetype) = ['',''];
                if(!$written){
                    $size = $file["size"];
                    $mimetype = $file["mimetype"];
                }
                $fileTemp[]=["Nom métier:",$file["name"], $mimetype, $size];
            }
            $this->fichier_table[]=$fileTemp;
        }
    }

    public function initcycle_table(){
        //traiter des requêtes
        $workflow = $this->actesTransaction->fetchWorkflow();
        $status = $this->actesTransaction->getStatusList();

        $this->cycle_table=[];
		foreach ($workflow as $stage) {
            $this->cycle_table[] = [
                $status[$stage["status_id"]],
                Helpers :: getDateFromBDDDate($stage["date"], true),
                $stage["message"]
            ];
        }
    }
}