<?php


class ExtractDataForBordereauPDF{


    /**
     * @var TransactionSQL
     */
    private $transactionSQL;
    /**
     * @var ActesIncludedFileSQL
     */
    private $actesIncludedFileSQL;
    /**
     * @var ActesStatusSQL
     */
    private $actesStatusSQL;

    public function __construct(TransactionSQL $transactionSQL, ActesIncludedFileSQL $actesIncludedFileSQL, ActesStatusSQL $actesStatusSQL)
    {
        $this->transactionSQL = $transactionSQL;
        $this->actesIncludedFileSQL = $actesIncludedFileSQL;
        $this->actesStatusSQL = $actesStatusSQL;
    }

    public function extract($transactionId,$addEmailNotificationField=false){

        $this->transactionSQL->setTransmissionId($transactionId);
        $transaction = $this->transactionSQL->getAll();
        $transactionComplement = $this->transactionSQL->getComplement($transactionId);
        $workflow = $this->transactionSQL->fetchWorkflow($transactionId);
        $status = $this->actesStatusSQL->getAllStatus();
        $includedFiles = $this->actesIncludedFileSQL->getAll($transactionId);

        $data = new DataForBordereauPDF();

        $data->setTexteCollectivite($transaction[0]["authority_name"]);
        $data->setTexteUtilisateur($transaction[0]["name"],$transaction[0]["givenname"]);
        $data->setContenuTableau($this->initDataTable($transaction, $transactionComplement, $addEmailNotificationField));
        $data->setFichierTable($this->initDatafichier_table($includedFiles));
        $data->setCycleTable($this->initcycle_table($workflow,$status));

        return $data;
    }

    private function getNotifieA(array $transactionComplement,bool $addEmailNotificationField){
        if ($transactionComplement["broadcasted"] == 't' ) {
            return "Notifiée à " . $transactionComplement["broadcast_emails"];
        }
        if ($addEmailNotificationField && $transactionComplement["broadcast_emails"]){
            return "Notifiée à " . $transactionComplement["broadcast_emails"];
        }
        return "Non notifiée";
    }

    public function initDataTable(array $transaction, array $transactionComplement, bool $addEmailNotificationField){
        //traiter des requêtes
        if(isset($transaction[0]["nature_descr"])){
            $nature_description = $transaction[0]["nature_descr"];
        } else {
            $nature_description = "n/a";
        }

        $notification = $this->getNotifieA($transactionComplement,$addEmailNotificationField);

        $classification = $transactionComplement["classification"];
        $classification_string = $transactionComplement["classification_string"];

        if ($classification_string) {
            $classification .= " - $classification_string";
        }

        $arch_url = $transaction[0]["archive_url"];
        if (empty($arch_url))
            $arch_url= "Non définie";

        return [
            ["Type de transaction:",$transaction[0]["type_str"]],
            ["Nature de l'acte:",$nature_description],
            ["Numéro de l'acte:",$transaction[0]["number"]],
            ["Date de la décision:",$transactionComplement["decision_date"]],
            ["Objet:",$transaction[0]["subject"]],
            ["Documents papiers complémentaires:",$transactionComplement["document_papier"]?"OUI":"NON"],
            ["Classification matières/sous-matières:",$classification],
            ["Identifiant unique:",$transactionComplement["unique_id"]],
            ["URL d'archivage:",$arch_url],
            ["Notification:",$notification]
        ];
    }

    public function initDatafichier_table($files){
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
        return $filesTemp;
    }

    public function initcycle_table(array $workflow,array $status){
        //traiter des requêtes
        $cycle_table=[];
		foreach ($workflow as $stage) {
            $cycle_table[] = [
                $status[$stage["status_id"]],
                Helpers :: getDateFromBDDDate($stage["date"], true),
                $stage["message"]
            ];
        }
		return $cycle_table;
    }
}