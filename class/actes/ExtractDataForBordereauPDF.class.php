<?php


class ExtractDataForBordereauPDF{

    /**
     * @var ActesTransactionFactory
     */
    private $actesTransactionFactory;
    /**
     * @var UserFactory
     */
    private $userFactory;
    /**
     * @var AuthorityFactory
     */
    private $authorityFactory;

    public function __construct(ActesTransactionFactory $actesTransactionFactory, UserFactory $userFactory, AuthorityFactory $authorityFactory)
    {
        $this->actesTransactionFactory = $actesTransactionFactory;
        $this->userFactory = $userFactory;
        $this->authorityFactory = $authorityFactory;
    }

    public function extract($transactionId,$addEmailNotificationField=false){

        $data = new DataForBordereauPDF();

        $transaction = $this->actesTransactionFactory->get($transactionId);
        $user = $this->userFactory->getUserByEnvelopeId($transaction->get("envelope_id"));
        $author = $this->authorityFactory->get($user->get("authority_id"));

        $data->setTexteCollectivite($author->get("name"));
        $data->setTexteUtilisateur($user->get("name"),$user->get("givenname"));

        $data->setContenuTableau($this->initDataTable($transaction, $addEmailNotificationField));

        $data->setFichierTable($this->initDatafichier_table($transaction));

        $data->setCycleTable($this->initcycle_table($transaction));

        return $data;
    }

    private function getNotifieA(ActesTransaction $trans, bool $addEmailNotificationField){
        if ($trans->get("broadcasted") == 't' ) {
            return "Notifiée à " . $trans->get("broadcast_emails");
        }
        if ($addEmailNotificationField && $trans->get("broadcast_emails")){
            return "Notifiée à " . $trans->get("broadcast_emails");
        }
        return "Non notifiée";
    }

    public function initDataTable(ActesTransaction $transaction, bool $addEmailNotificationField){
        //traiter des requêtes
        $transactionTypes = $transaction->get("transactionTypes");
        $transNatures = ActesTransaction :: getTransactionNaturesIdDescr();

        if(isset($transNatures[$transaction->get("nature_code")])){
            $nature_description = $transNatures[$transaction->get("nature_code")];
        } else {
            $nature_description = "n/a";
        }

        $notification = $this->getNotifieA($transaction, $addEmailNotificationField);

        $classification = $transaction->get("classification") ;
        $classification_string = $transaction->get('classification_string');
        if ($classification_string) {
            $classification .= " - $classification_string";
        }

        $arch_url = $transaction->get("archive_url");
        if (empty($arch_url))
            $arch_url= "Non définie";

        return [
            ["Type de transaction:",$transactionTypes[$transaction->get("type")]],
            ["Nature de l'acte:",$nature_description],
            ["Numéro de l'acte:",$transaction->get("number")],
            ["Date de la décision:",$transaction->get("decision_date")],
            ["Objet:",$transaction->get("subject")],
            ["Documents papiers complémentaires:",$transaction->getDocumentPapier()?"OUI":"NON"],
            ["Classification matières/sous-matières:",$classification],
            ["Identifiant unique:",$transaction->get("unique_id")],
            ["URL d'archivage:",$arch_url],
            ["Notification:",$notification]
        ];
    }

    public function initDatafichier_table(ActesTransaction $transaction ){
        $files = $transaction->fetchFilesList();
        $filesTemp = [];
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
            $filesTemp[]= $fileTemp;
        }
        return $filesTemp;
    }

    public function initcycle_table(ActesTransaction $transaction){
        //traiter des requêtes
        $workflow = $transaction->fetchWorkflow();
        $status = $transaction->getStatusList();

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