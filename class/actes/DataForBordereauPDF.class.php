<?php


class DataForBordereauPDF
{
    /** @var string  */
    private $texteCollectivite;
    /** @var string  */
    private $texteUtilisateur;

    /** @var array */
    private $fichier_table;

    private $cycle_table;
    /**
     * @var mixed
     */
    private $nature_description;
    private $broadcasted;
    /**
     * @var mixed
     */
    private $broadcastEmails;
    private $addEmailNotificationField;
    private $classification;
    private $arch_url;
    private $typeDeTransaction;
    private $numeroActe;
    private $dateDecision;
    private $objet;
    private $presenceDocPapier;
    private $idUnique;

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
        return [
            ["Type de transaction:",$this->typeDeTransaction],
            ["Nature de l'acte:",$this->nature_description],
            ["Numéro de l'acte:",$this->numeroActe],
            ["Date de la décision:",$this->dateDecision],
            ["Objet:",$this->objet],
            ["Documents papiers complémentaires:",$this->presenceDocPapier],
            ["Classification matières/sous-matières:",$this->classification],
            ["Identifiant unique:",$this->idUnique],
            ["URL d'archivage:",$this->arch_url],
            ["Notification:",$this->getNotifieA()]
        ];
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

    public function setIncludedFiles(array $files)
    {
        $filesTemp = [];
        foreach ($files as $file) {
            $fileTemp=[];
            $written = false;
            if ($file["posted_filename"])
            {

                $fileTemp[]=["Nom original :",$file["posted_filename"],$file["filetype"],$file["filesize"]];
                $written = true;

            }
            if ($file["filename"])
            {
                list($size,$mimetype) = ['',''];
                if(!$written){
                    $size = $file["filesize"];
                    $mimetype = $file["filetype"];
                }
                $fileTemp[]=["Nom métier:",$file["filename"], $mimetype, $size];
            }
            $filesTemp[]= $fileTemp;
        }
        $this->fichier_table = $filesTemp;
    }

    public function setCycleTable($workflow,$status)
    {
        //traiter des requêtes
        $cycle_table=[];
        foreach ($workflow as $stage) {
            $cycle_table[] = [
                $status[$stage["status_id"]],
                Helpers :: getDateFromBDDDate($stage["date"], true),
                $stage["message"]
            ];
        }
        $this->cycle_table = $cycle_table;
    }

    public function setAddEmailNotificationField($addEmailNotificationField){
        $this->addEmailNotificationField = $addEmailNotificationField;
    }

    /**
     * @return string
     */
    private function getNotifieA(){
        if (($this->broadcasted == 't') || ($this->addEmailNotificationField && $this->broadcastEmails) ) {
            return "Notifiée à " . $this->broadcastEmails;
        }
        return "Non notifiée";
    }

    public function setDonneesTransaction(array $transaction, array $transactionComplement){
        $this->texteCollectivite = $transaction[0]["authority_name"];
        $this->texteUtilisateur = $transaction[0]["name"]." ".$transaction[0]["givenname"];

        //traiter des requêtes
        if(isset($transaction[0]["nature_descr"])){
            $this->nature_description = $transaction[0]["nature_descr"];
        } else {
            $this->nature_description = "n/a";
        }

        $this->broadcasted = $transactionComplement["broadcasted"];
        $this->broadcastEmails = $transactionComplement["broadcast_emails"];

        $this->classification = $transactionComplement["classification"];
        $classification_string = $transactionComplement["classification_string"];

        if ($classification_string) {
            $this->classification .= " - $classification_string";
        }

        $this->arch_url = $transaction[0]["archive_url"];
        if (empty($arch_url))
            $this->arch_url= "Non définie";

        $this->typeDeTransaction = $transaction[0]["type_str"];

        $this->numeroActe = $transaction[0]["number"];
        $this->dateDecision = $transactionComplement["decision_date"];
        $this->objet = $transaction[0]["subject"];
        $this->presenceDocPapier = $transactionComplement["document_papier"]?"OUI":"NON";
        $this->idUnique = $transactionComplement["unique_id"];
    }

}