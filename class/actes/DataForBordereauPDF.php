<?php

namespace S2lowLegacy\Class\actes;

use S2lowLegacy\Class\Helpers;

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
    /** @var ClassificationString */
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
            ["Type de transaction :",$this->typeDeTransaction],
            ["Nature de l'acte :",$this->nature_description],
            ["Numéro de l'acte :",$this->numeroActe],
            ["Date de la décision :",$this->dateDecision],
            ["Objet :",$this->objet],
            ["Documents papiers complémentaires :",$this->presenceDocPapier],
            ["Classification matières/sous-matières :",$this->classification->get()],
            ["Identifiant unique :",$this->idUnique],
            ["URL d'archivage :",$this->arch_url],
            ["Notification :",$this->getNotifieA()]
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
        $this->fichier_table = $files;
    }

    public function setCycleVieTransaction($workflow, $status)
    {
        //traiter des requêtes
        $cycle_table = [];
        foreach ($workflow as $stage) {
            $cycle_table[] = [
                $status[$stage["status_id"]],
                \S2lowLegacy\Class\Helpers\DateHelper::getDateFromBDDDate($stage["date"], true),
                $stage["message"]
            ];
        }
        $this->cycle_table = $cycle_table;
    }

    public function setAddEmailNotificationField($addEmailNotificationField)
    {
        $this->addEmailNotificationField = $addEmailNotificationField;
    }

    /**
     * @return string
     */
    private function getNotifieA()
    {
        if (($this->broadcasted == 't') || ($this->addEmailNotificationField && $this->broadcastEmails)) {
            return "Notifiée à " . $this->broadcastEmails;
        }
        return "Non notifiée";
    }


    public function setDonneesTransaction(array $transactionComplement)
    {
        $this->texteCollectivite = $transactionComplement["authority_name"];
        $this->texteUtilisateur = $transactionComplement["name"] . " " . $transactionComplement["givenname"];

        $this->nature_description = $transactionComplement["nature_descr"] ?? "n/a";

        $this->broadcasted = $transactionComplement["broadcasted"];
        $this->broadcastEmails = $transactionComplement["broadcast_emails"];

        $this->classification = new ClassificationString($transactionComplement);


        $this->arch_url = $transactionComplement["archive_url"] ? : "Non définie";
        ;

        $this->typeDeTransaction = $transactionComplement["type_str"];

        $this->numeroActe = $transactionComplement["number"];
        $this->dateDecision = $transactionComplement["decision_date"];
        $this->objet = $transactionComplement["subject"];
        $this->presenceDocPapier = $transactionComplement["document_papier"] ? "OUI" : "NON";
        $this->idUnique = $transactionComplement["unique_id"];
    }
}
