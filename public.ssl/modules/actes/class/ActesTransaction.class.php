<?php

use S2low\Services\ProcessCommand\CommandLauncher;
use S2low\Services\ProcessCommand\OpenSSLWrapper;
use S2lowLegacy\Class\actes\ActesClassificationCodesSQL;
use S2lowLegacy\Class\actes\ActesStatusSQL;
use S2lowLegacy\Class\actes\TypeTransaction;
use S2lowLegacy\Class\DatabasePool;
use S2lowLegacy\Class\DataObject;
use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\VerifyPemCertificateFactory;
use S2lowLegacy\Class\VerifyPKCS7Signature;
use S2lowLegacy\Class\XMLHelper;
use S2lowLegacy\Lib\PemCertificateFactory;

class ActesTransaction extends DataObject
{
    //Constante pour les messages
    // DemandePieceComplementaire et LettreDObservation
    public const TYPE_REFUS = 3;
    public const TYPE_ENVOIE = 4;
    public const NUMBER_REGEXP = '/^([A-Z0-9][A-Z0-9_]{0,13})?[A-Z0-9]$/';
    public $files = array ();
    protected $objectName = "actes_transactions";
    protected $envelope_id;
    protected $type;
    protected $related_transaction_id;
    protected $nature_code;
    protected $nature_descr;
    protected $subject;
    protected $number;
    protected $classif1;
    protected $classif2;
    protected $classif3;
    protected $classif4;
    protected $classif5;
    protected $classification;
    protected $classification_date;
    protected $decision_date;
    protected $unique_id;
    protected $archive_url;
    protected $broadcasted;
    protected $broadcast_send_sources;
    protected $broadcast_emails;
    protected $last_status_id; //pour les message 3 et 4, les types de réponse 3=> REJET 4=> ACCEPTE
    protected $type_reponse;
    protected $related_transaction;
    protected $last_classification_date;
    protected $xmlFileName;
    protected $xmlFilesize;
    protected $xmlObj;
    protected $rootDir;
    protected $destDir;
    protected $related_id;
    protected $user_id;

    protected $classification_string;
    protected $document_papier = 0;

    protected $dbFields = array (
    "envelope_id" => array (
      "descr" => "Identifiant enveloppe",
      "type" => "isInt",
      "mandatory" => true
    ),
    "type" => array (
      "descr" => "Type de transaction",
      "type" => "isString",
      "mandatory" => true
    ),
    "related_transaction_id" => array (
      "descr" => "Transaction reliée",
      "type" => "isInt",
      "mandatory" => false
    ),
    "nature_code" => array (
      "descr" => "Nature de l'acte",
      "type" => "isString",
      "mandatory" => true
    ),
    "nature_descr" => array (
      "descr" => "Description nature de l'acte",
      "type" => "isString",
      "mandatory" => true
    ),
    "subject" => array (
      "descr" => "Objet de l'acte",
      "type" => "isString",
      "maxlength" => 499,
      "mandatory" => true
    ),
    "number" => array (
      "descr" => "Numéro de l'acte",
      "type" => "isString",
      "maxlength" => 15,
      "mandatory" => true,
        //Règle original sur s2low depuis le début
        //"regexp" => '/^[0-9A-Z][0-9A-Z_]*[0-9A-Z]$/',

        // Ci-dessous, c'est la bonne règle qui est dans le XSD @ctes
        //"regexp" => '/^([a-zA-Z0-9][a-zA-Z0-9\-_&#x20;]{0,13})?[a-zA-Z0-9]$/',

        //Règle intermédiaire
        "regexp" => self::NUMBER_REGEXP,

      "regexp_txt" => "ne peut contenir que des chiffres, des lettres en majuscules et _"
    ),
    "classification" => array (
      "descr" => "Classification matières/sous-matières",
      "type" => "isString",
      "mandatory" => true
    ),
    "classification_date" => array (
      "descr" => "Date classification matières/sous-matières utilisée",
      "type" => "isDate",
      "mandatory" => true
    ),
    "decision_date" => array (
      "descr" => "Date de la décision",
      "type" => "isDate",
      "mandatory" => true
    ),
    "unique_id" => array (
      "descr" => "Identifiant unique",
      "type" => "isString",
      "mandatory" => false
    ),
    "archive_url" => array (
      "descr" => "URL d'accès à l'archive",
      "type" => "isString",
      "maxlength" => 1023,
      "mandatory" => false
    ),
    "broadcast_send_sources" => array (
      "descr" => "Joindre ou non les sources à la notification",
      "type" => "isString",
      "maxlength" => 1023,
      "mandatory" => false
    ),
    "broadcast_emails" => array (
      "descr" => "Liste des destinataires de la notification",
      "type" => "isString",
      "maxlength" => 1023,
      "mandatory" => false
    ),
    "broadcasted" => array (
      "descr" => "Indique si la notification a été effectuée",
      "type" => "isString",
      "maxlength" => 1023,
      "mandatory" => false
    ),
     "type_reponse" => array (
      "descr" => "Type de la réponse pour les réponse ministère LO et Demande de PC",
      "type" => "isInt",
      "mandatory" => false
    ),
    "last_status_id" => array (
      "descr" => "",
      "type" => "isInt",
      "mandatory" => false
    ),
    "user_id" => array (
      "descr" => "",
      "type" => "isInt",
      "mandatory" => false
    ),
    "authority_id" => array (
      "descr" => "",
      "type" => "isInt",
      "mandatory" => false
    ),
    "sae_transfer_identifier" => array(
      "descr" => "",
      "type" => "isString",
        "maxlength" => 256,
      "mandatory" => false
    ),
      "classification_string" => array(
          "descr" => "",
          "type" => "isString",
          "maxlength" => 256,
          "mandatory" => false
      ),
      "document_papier" => array(
          "descr" => "indique si la télétransmission est suivi d'un envoi de piece papier",
          "type" => "isInt",
          "mandatory" => false
      ),
    );
    protected $transactionTypes = array (
    "1" => "Transmission d'actes",
    "2" => "Courrier simple",
    "3" => "Demande de pièces complémentaires",
    "4" => "Lettre d'observation",
    "5" => "Déféré au Tribunal Administratif",
    "6" => "Annulation",
    "7" => "Demande de classification"
    );  // Utilisé !
    protected $en_attente;
    protected $is_en_attente_de_signature;
    private $fileNameSerial;
    private $workflow = array ();

  /**
   * \brief Constructeur d'une transaction
   * \param id integer Numéro d'identifiant d'une transaction existante avec laquelle initialiser l'objet
   */
    public function __construct($id = false)
    {
        parent :: __construct($id);

        $this->fileNameSerial = 1;
    }

    public static function getTypeReponse($transactionType, $reponseType)
    {

        $typeReponse = array(
        3   => array(4 => "Transmission de pièces complémentaires",
                    3 => "Refus explicite d'envoi de pièces complémentaires"
                    ),
        4 => array(4 => "Lettre de justification de l'acte",
                    3 => "Rejet explicite d'une lettre d'observations")
        );
        if (empty($typeReponse[$transactionType][$reponseType])) {
            return false;
        }
        return $typeReponse[$transactionType][$reponseType];
    }

  /**
   * \brief Méthode de récupération des natures de transaction
   * \return Un tableau de natures de transaction
   *
   * Cette méthode renvoie un tableau dont les clefs sont l'identifiant numérique
   * de la nature et dont les valeurs sont la description de la nature de transaction
   *
   */
    public static function getTransactionNaturesIdDescr()
    {
        $sql = "SELECT id, descr FROM actes_natures ORDER BY descr ASC";

        $db = DatabasePool :: getInstance();

        $result = $db->select($sql);

        $types = array ();

        if (!$result->isError()) {
            while ($row = $result->get_next_row()) {
                $types[$row["id"]] = $row["descr"];
            }
        }

        return $types;
    }

  /**
   * \brief Méthode d'obtention de la liste des statuts des transactions
   * \return Tableau des statuts de transactions
  */
    public static function getStatusList()
    {
        $sql = "SELECT id, name FROM actes_status";

        $db = DatabasePool :: getInstance();

        $result = $db->select($sql);

        $types = array ();

        if (!$result->isError()) {
            while ($row = $result->get_next_row()) {
                $types[$row["id"]] = $row["name"];
            }
        }

        return $types;
    }

  /**
   * \brief Méthode permettant de fixer la valeur d'un attribut
   * \param $name chaîne : Nom de l'attribut
   * \param $val : valeur de l'attribut
  */
    public function set($name, $val)
    {
        switch ($name) {
            case "decision_date":
            case "classification_date":
                if (!preg_match("/^[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}$/", $val)) {
                    return false;
                }
                break;
            case "destDir":
                parent :: set("rootDir", ACTES_FILES_UPLOAD_ROOT);
                break;
        }

        parent :: set($name, $val);
    }

  /**
   * \brief Méthode vérifiant qu'aucune autre transaction n'a le même numéro interne pour la collectivité
   * \param $authority_id entier : Identifiant de la collectivité concernée
   * \return True si la transaction est unique, false sinon
  */
    public function isUnique($authority_id)
    {
        if (isset($this->number)) {
            $sql = "SELECT actes_transactions.id FROM actes_transactions LEFT JOIN actes_envelopes ON actes_transactions.envelope_id=actes_envelopes.id LEFT JOIN users ON actes_envelopes.user_id=users.id WHERE actes_transactions.number=? AND users.authority_id=?";

            $result = $this->db->select($sql, [$this->number, $authority_id]);

            if (!$result->isError()) {
                if ($result->num_row() > 0) {
                    return false;
                } else {
                    return true;
                }
            } else {
                return false;
            }
        }
    }

  /**
   * \brief Méthode permettant de récuperer l'enveloppe de retour si elle existe
   * \param $status_id entier : l'enveloppe attaché au status de la transaction
   * \return le flux XML de retour
   */
    public function getFluxRetour($status_id)
    {
        $sql = "SELECT flux_retour FROM actes_transactions_workflow" .
                " WHERE transaction_id = ? AND status_id = ?";

        $pdo = $this->db->getPdo();

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$this->id,$status_id]);
        $stmt->bindColumn(1, $flux_retour, PDO::PARAM_LOB);
        $stmt->fetch(PDO::FETCH_BOUND);
        if (is_null($flux_retour)) {
            return false;
        }
        $flux_retour_contents = stream_get_contents($flux_retour);
        fclose($flux_retour);

        return $flux_retour_contents;
    }

  /**
   * \brief Méthode d'obtention de l'état courant d'un transaction
   * \return L'identifiant de l'état courant de la transaction
   */
    public function getCurrentStatus()
    {
        if (isset($this->id) && !empty($this->id)) {
            $sql = "SELECT status_id FROM actes_transactions_workflow atw WHERE date = ( SELECT MAX(date) FROM actes_transactions_workflow atw2 WHERE atw2.transaction_id = atw.transaction_id) AND transaction_id = ? ORDER BY atw.id DESC LIMIT 1";

            $result = $this->db->select($sql, [$this->id]);

            if (!$result->isError()) {
                $row = $result->get_next_row();
                return $row["status_id"];
            }

            return false;
        }
        return false;
    }


    public function getCurrentMesssage()
    {
        if (! $this->id) {
            return false;
        }
        $sql = "SELECT message FROM actes_transactions_workflow atw WHERE date = ( SELECT MAX(date) FROM actes_transactions_workflow atw2 WHERE atw2.transaction_id = atw.transaction_id) AND transaction_id = " . $this->id . " ORDER BY atw.id DESC LIMIT 1";
        $result = $this->db->select($sql);
        if ($result->isError()) {
            return false;
        }
        $row = $result->get_next_row();
        return $row["message"];
    }

  /**
  * \brief Méthode qui positionne les éléments relatifs à une notification manuelle
  * \param $emails chaîne : emails des destinataires
  * \param $send_sources entier : Envoi des fichiers sources (0/1)
  * \return True en cas de succès, false sinon
  */
    public function setNotification($emails, $send_sources)
    {
        $sql = "UPDATE actes_transactions SET broadcast_send_sources = " . $send_sources . ", broadcast_emails = ? WHERE id = ?";

        if (!$this->db->exec($sql, [$emails,$this->id])) {
            $this->errorMsg = "Erreur lors de la définition des paramètres de notification.";
            return false;
        }

        return true;
    }

  /**
   * \brief Méthode retournant un nom de fichier (non enveloppe) formaté au standard de la transmission Actes
   * \param $env ActesEnvelope : Object enveloppe correspondant
   * \param $use_serial boolean (optionnel) : Ajouter le numéro de série à la fin du nom de fichier puis l'incrémenter (true par défaut)
   * \return Le nom du fichier sans extension
   */
    public function getStdFileName(ActesEnvelope $env, $use_serial = true, $code_pj = '')
    {
        if ($this->isType(TypeTransaction::Annulation)) {
            $trans = $this->related_transaction;
        } else {
            $trans = $this;
        }

        $nature_descr = ActesTransaction :: getTransactionNatureDescr($trans->nature_code);

      // Racine
        $name = $this->destDir . "/";

        if ($code_pj) {
            $name .= $code_pj . "-";
        }

      // Nom du fichier
      // Département
        $name .= $env->get("department");

      // Siren
        $name .= "-" . $env->get("siren");

      // Date de l'acte YYYYMMDD
        $name .= "-";
        if (!$this->isType(TypeTransaction::DemandeDeClassification)) {
            $name .= date("Ymd", Helpers :: ansiDateToTimestamp($trans->decision_date));
        }

      // Numéro de l'acte interne à la collectivité
        $name .= "-";
        if (!$this->isType(TypeTransaction::DemandeDeClassification)) {
            $name .= $trans->number;
        }

      // Code de la nature de l'acte
        $name .= "-";
        if (!$this->isType(TypeTransaction::DemandeDeClassification)) {
            $name .= $nature_descr["short_descr"];
        }

      // Type de message
        switch ($this->getType()) {
            case TypeTransaction::TransmissionActe:
                $name .= "-1-1";
                break;
            case TypeTransaction::CourrierSimple:
                $name .= "-2-2";
                break;
            case TypeTransaction::DemandePieceComplementaire:
                $name .= "-3-" . $this->type_reponse;
                break;
            case TypeTransaction::LettreDObservation:
                $name .= "-4-" . $this->type_reponse;
                break;
            case TypeTransaction::Annulation:
                $name .= "-6-1";
                break;
            case TypeTransaction::DemandeDeClassification:
                $name .= "-7-1";
                break;
        }

        if ($use_serial) {
          // Numéro de série
            $name .= "_" . $this->fileNameSerial;

            $this->fileNameSerial++;
        }

        return $name;
    }

  /**
   * \brief Méthode de récupération des descriptions courtes et longue de la nature d'un acte en fonction de son identifiant
   * \param $id integer : identifiant de la nature à rechercher
   * \return Un tableau associatif contenant les descriptions ou false en cas d'erreur
   *
   */
    public static function getTransactionNatureDescr($id)
    {
        $id = intval($id);
        if (!empty($id)) {
            $sql = "SELECT short_descr, descr FROM actes_natures WHERE id=" . $id;

            $db =  DatabasePool :: getInstance();

            $result = $db->select($sql);

            if (!$result->isError() && $result->num_row() == 1) {
                $row = $result->get_next_row();
                return array (
                "short_descr" => $row["short_descr"],
                "descr" => $row["descr"]
                );
            }
        }

        return false;
    }

  /**
   * \brief Méthode d'ajout d'une signature de l'acte
   * \param $sign chaîne : Chemin du fichier dans le système de fichier ou signature sous forme de chaine
   * \param $readFile booléen : lire ou non le fichier (donc $sign est un nom de fichier)
   * \return True en cas de succès, false sinon
   */
    public function addActeSign($sign, $readFile = true)
    {
        return $this->addSign("acte", $sign, $readFile);
    }

  /**
   * \brief Méthode d'ajout d'une signature d'un fichier
   * \param $type chaîne : Type de fichier, "acte" ou "attachment"
   * \param $sign chaîne : Chemin du fichier dans le système de fichier ou signature sous forme de chaine
   * \param $readFile booléen : lire ou non le fichier (donc $sign est un nom de fichier)
   * \return True en cas de succès, false sinon
   */
    public function addSign($type, $sign, $readFile)
    {
      // Lecture du contenu du fichier
        if ($readFile) {
            if (($signStr = file_get_contents($sign)) === false) {
                $this->errorMsg = "Erreur lors de la lecture du fichier signature";
                return false;
            } else {
              // Suppression retour à la ligne éventuel
                $signStr = rtrim($signStr);
            }
        } else {
            $signStr = rtrim($sign);
        }

        if (!$this->storeSign($type, $signStr)) {
            return false;
        }

        return true;
    }

  /**
   * \brief Méthode de stockag ede la signature dans le tableau des fichiers
   * \param $type chaîne : Type de fichier, "acte" ou "attachment"
   * \param $sign chaîne : Chaîne de signature
   */
    public function storeSign($type, $sign)
    {
        if ($type == "acte") {
            $this->files[$type]["sign"] = $sign;
        } elseif ($type == "attachment") {
            $file_num = count($this->files[$type]) - 1;
            $this->files[$type][$file_num]["sign"] = $sign;
        } else {
            return false;
        }

        return true;
    }

  /**
   * \brief Méthode d'ajout d'une signature de pièce jointe
   * \param $sign chaîne : Chemin du fichier dans le système de fichier ou signature sous forme de chaine
   * \param $readFile booléen : lire ou non le fichier (donc $sign est un nom de fichier)
   * \return True en cas de succès, false sinon
   */
    public function addAttachmentSign($sign, $readFile = true)
    {
        return $this->addSign("attachment", $sign, $readFile);
    }

  /**
   * \brief Méthode de création du message métier XML de la transaction
   * \param $xml_name chaîne : Nom du fichier à créer
   * \return True en cas de succès, false sinon
   */
    public function generateMessageXMLFile($xml_name)
    {
        switch ($this->getType()) {
            case TypeTransaction::TransmissionActe:
                $xml = $this->generateActeXMLFile($xml_name);
                break;

            case TypeTransaction::CourrierSimple:
            case TypeTransaction::DemandePieceComplementaire:
            case TypeTransaction::LettreDObservation:
                $xml = $this->generateReponseCourrierXMLFile($xml_name);
                break;

            case TypeTransaction::Annulation:
                $xml = $this->generateCancelXMLFile($xml_name);
                break;
            case TypeTransaction::DemandeDeClassification:
                $xml = $this->generateClassifRequestXMLFile($xml_name);
                break;
            default:
                $this->errorMsg = 'Mauvais type de transaction.';
                return false;
        }

        if ($xml === false) {
            return false;
        }

        $xml_name .= "_0.xml";
        $this->xmlFileName = $xml_name;

        if (!Helpers :: createDirTree(dirname($this->rootDir . "/" . $this->xmlFileName))) {
            $this->errorMsg = "Erreur système de fichiers (createDirTree).";
            return false;
        }

        if (! file_put_contents($this->rootDir . "/" . $this->xmlFileName, $xml)) {
            $this->errorMsg = "Erreur système de fichiers (file_put_contents ) : " . $this->rootDir . "/" . $this->xmlFileName;
            return false;
        }

        if (!$this->xmlFilesize = @ filesize($this->rootDir . "/" . $this->xmlFileName)) {
            $this->errorMsg = "Erreur système de fichiers (filesize).";
            return false;
        }

        return true;
    }

  /**
   * \brief Méthode de création du message métier XML d'une transmission d'acte
   * \param $xml_name chaîne : Nom du fichier à créer
   * \return Le XML généré ou false en cas d'échec
   */
    public function generateActeXMLFile(string $xml_name): false|string
    {
        $xml = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>\n";
        $xml .= "<actes:Acte\n";
        $xml .= "xmlns:actes=\"http://www.interieur.gouv.fr/ACTES#v1.1-20040216\"\n";
        $xml .= "xmlns:insee=\"http://xml.insee.fr/schema\"\n";
        $xml .= "xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n";
        $xml .= "xsi:schemaLocation=\"http://www.interieur.gouv.fr/ACTES#v1.1-20040216 actesv1_1.xsd\"\n";
        $xml .= "actes:Date=\"" . date("Y-m-d", Helpers :: ansiDateToTimestamp($this->decision_date)) . "\"\n";
        $xml .= "actes:NumeroInterne=\"" . Helpers :: escapeForXML($this->number) . "\"\n";
        $xml .= "actes:CodeNatureActe=\"" . Helpers :: escapeForXML($this->nature_code) . "\">\n";

      // Les codes matières
        for ($i = 1; $i <= 5; $i++) {
            $var = "classif" . $i;
            if (isset($this-> $var) && !empty($this-> $var) && is_numeric($this-> $var)) {
                $xml .= " <actes:CodeMatiere" . $i . " actes:CodeMatiere=\"" . $this-> $var . "\"/>\n";
            }
        }
        $xml .= " <actes:Objet>" . XMLHelper::convertToIsoAndEscape($this->subject ?? "") . "</actes:Objet>\n";
        $xml .= " <actes:ClassificationDateVersion>" . date("Y-m-d", Helpers :: ansiDateToTimestamp($this->classification_date)) . "</actes:ClassificationDateVersion>\n";
        $xml .= " <actes:Document>\n";
        $xml .= "  <actes:NomFichier>" . Helpers :: escapeForXML(basename($this->files["acte"]["name"])) . "</actes:NomFichier>\n";

        if (isset($this->files["acte"]["sign"])) {
            $xml .= "  <actes:Signature>" . Helpers :: escapeForXML($this->files["acte"]["sign"]) . "</actes:Signature>\n";
        }

        $xml .= " </actes:Document>\n";
        $xml .= " <actes:Annexes actes:Nombre=\"" . (isset($this->files["attachment"]) ? count($this->files["attachment"]) : 0) . "\">\n";

        if (isset($this->files["attachment"])) {
            foreach ($this->files["attachment"] as $key => $file) {
                $xml .= "  <actes:Annexe>\n";
                $xml .= "   <actes:NomFichier>" . Helpers :: escapeForXML(basename($file["name"])) . "</actes:NomFichier>\n";
                if (isset($file["sign"])) {
                    $xml .= "  <actes:Signature>" . Helpers :: escapeForXML($file["sign"]) . "</actes:Signature>\n";
                }
                $xml .= "  </actes:Annexe>\n";
            }
        }

        $xml .= " </actes:Annexes>\n";
        $xml .= "<actes:DocumentPapier>" . ($this->getDocumentPapier() ? "O" : "N") . "</actes:DocumentPapier>\n";
        $xml .= "</actes:Acte>\n";

        return $xml;
    }

    public function getDocumentPapier()
    {
        return $this->document_papier;
    }

    public function generateReponseCourrierXMLFile($xml_name)
    {
        switch ($this->getType()) {
            case TypeTransaction::CourrierSimple:
                $root =  "ReponseCourrierSimple";
                break;
            case TypeTransaction::DemandePieceComplementaire:
                if ($this->type_reponse == ActesTransaction::TYPE_REFUS) {
                    $root = "RefusPieceComplementaire";
                } elseif ($this->type_reponse == ActesTransaction::TYPE_ENVOIE) {
                    $root = "PieceComplementaire";
                } else {
                    $this->errorMsg = "Vous devez choisir un type de réponse";
                    return false;
                }
                break;
            case TypeTransaction::LettreDObservation:
                if ($this->type_reponse == ActesTransaction::TYPE_REFUS) {
                    $root = "RejetLettreObservations";
                } elseif ($this->type_reponse == ActesTransaction::TYPE_ENVOIE) {
                    $root = "ReponseLettreObservations";
                } else {
                    $this->errorMsg = "Vous devez choisir un type de réponse";
                    return false;
                }
                break;
            default:
                return null;
        }


        $xml = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>\n";
        $xml .= "<actes:$root xmlns:actes=\"http://www.interieur.gouv.fr/ACTES#v1.1-20040216\"\n";
        $xml .= "xmlns:insee=\"http://xml.insee.fr/schema\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n";
        $xml .= "xsi:schemaLocation=\"http://www.interieur.gouv.fr/ACTES#v1.1-20040216 actesv1_1.xsd\"\n";
        $xml .= "actes:DateCourrierPref=\"" . $this->decision_date . "\" \n";
        $xml .= "actes:IDActe=\"" . Helpers :: escapeForXML($this->related_transaction->unique_id) . "\" > \n";

        if ($this->isType(TypeTransaction::DemandePieceComplementaire) && $this->type_reponse == ActesTransaction::TYPE_ENVOIE) {
            $xml .= "<actes:Documents>";
        }

        $xml .= "<actes:Document>";
        $xml .= "<actes:NomFichier>";
        $xml .= Helpers :: escapeForXML(basename($this->files["acte"]["name"]));
        $xml .= "</actes:NomFichier>\n";
        $xml .= "</actes:Document>\n";

        if ($this->isType(TypeTransaction::DemandePieceComplementaire) && $this->type_reponse == ActesTransaction::TYPE_ENVOIE) {
            if (isset($this->files["attachment"])) {
                foreach ($this->files["attachment"] as $key => $file) {
                    $xml .= "  <actes:Document>\n";
                    $xml .= "   <actes:NomFichier>" . Helpers :: escapeForXML(basename($file["name"])) . "</actes:NomFichier>\n";
                    $xml .= "</actes:Document>\n";
                }
            }
            $xml .= "</actes:Documents>";
        }

        $xml .= "</actes:$root>\n";

        return $xml;
    }

  /**
   * \brief Méthode de création du message métier XML d'une demande d'annulation
   * \param $xml_name chaîne : Nom du fichier à créer
   * \return Le XML généré ou false en cas d'échec
   */
    public function generateCancelXMLFile($xml_name)
    {
        $xml = null;

        if (!empty($this->related_transaction->unique_id)) {
            $xml = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>\n";
            $xml .= "<actes:Annulation\n";
            $xml .= "xmlns:actes=\"http://www.interieur.gouv.fr/ACTES#v1.1-20040216\"\n";
            $xml .= "xmlns:insee=\"http://xml.insee.fr/schema\"\n";
            $xml .= "xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n";
            $xml .= "xsi:schemaLocation=\"http://www.interieur.gouv.fr/ACTES#v1.1-20040216 actesv1_1.xsd\"\n";
            $xml .= "actes:IDActe=\"" . Helpers :: escapeForXML($this->related_transaction->unique_id) . "\"/>\n";
        } else {
            $this->errorMsg = "Info manquante pour générer le XML.";
        }

        return $xml;
    }

  /**
   * \brief Méthode de création du message métier XML d'une demande de classification
   * \param $xml_name chaîne : Nom du fichier à créer
   * \return Le XML généré ou false en cas d'échec
   */
    public function generateClassifRequestXMLFile($xml_name)
    {
        $xml = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>\n";
        $xml .= "<actes:DemandeClassification \n";
        $xml .= "xmlns:actes=\"http://www.interieur.gouv.fr/ACTES#v1.1-20040216\"\n";
        $xml .= "xmlns:insee=\"http://xml.insee.fr/schema\"\n";
        $xml .= "xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n";
        $xml .= "xsi:schemaLocation=\"http://www.interieur.gouv.fr/ACTES#v1.1-20040216 actesv1_1.xsd\">\n";

        if ($this->last_classification_date) {
            $xml .= " <actes:DateClassification>" . $this->last_classification_date . "</actes:DateClassification>\n";
        }

        $xml .= "</actes:DemandeClassification>\n";

        return $xml;
    }

  /**
   * \brief Méthode d'importation d'un fichier XML de description d'une transaction
   * \param $xmlFile chaîne : Chemin vers le fichier XML de description (relatif à ACTES_FILES_UPLOAD_ROOT)
   * \return True en cas de succès, false sinon
   * TODO : refactorer pour éviter l'import de $actesClassificationCodesSQL ...
  */
    public function createFromXML($xmlFile, ActesClassificationCodesSQL $actesClassificationCodesSQL)
    {


        $absXmlFile = ACTES_FILES_UPLOAD_ROOT . "/" . $xmlFile;
        $this->xmlFileName = $xmlFile;

        if (!file_exists($absXmlFile)) {
            $this->errorMsg = "Fichier XML introuvable.";
            return false;
        }

        if (!$this->xmlFilesize = filesize($absXmlFile)) {
            $this->errorMsg = "Erreur système.";
            return false;
        }

        if (($this->xmlObj = @ simplexml_load_file($absXmlFile)) === false) {
            $this->errorMsg = "Impossible d'importer le fichier message XML.";
            return false;
        }


      // Extraction des informations du XML pour initialiser la transaction
        $namespaces = $this->xmlObj->getDocNamespaces();
      // Récupération des éléments dans le namespace "actes"
        $actesItems = $this->xmlObj->children($namespaces["actes"]);

        $rep = true;

      // Détermination du type de transaction
        switch (@ dom_import_simplexml($this->xmlObj)->nodeName) {
            case "actes:Acte":
                $this->setType(TypeTransaction::TransmissionActe);
                $acte_attr = $this->xmlObj->attributes($namespaces["actes"]);
            // Date de la décision
                $this->decision_date = Helpers :: getFromXMLElt($acte_attr["Date"]);
            // Numéro interne
                $this->number = Helpers :: getFromXMLElt($acte_attr["NumeroInterne"]);
            // Nature de l'acte
                $this->nature_code = Helpers :: getFromXMLElt($acte_attr["CodeNatureActe"]);
                $descrs = ActesTransaction :: getTransactionNatureDescr($this->nature_code);
                $this->nature_descr = $descrs["descr"];

                $classification = array();

            // Classification matière
                for ($i = 1; $i <= 5; $i++) {
                    if (isset($actesItems-> {"CodeMatiere" . $i })) {
                        $classif_attr = $actesItems-> { "CodeMatiere" . $i } ->attributes($namespaces["actes"]);
                        if (isset($classif_attr["CodeMatiere"])) {
                                $this-> {"classif" . $i } = Helpers :: getFromXMLElt($classif_attr["CodeMatiere"]);
                            $classification[$i - 1] = $this->{"classif" . $i};
                        }
                    }
                }

            // Objet de l'acte
                $this->subject = Helpers :: getFromXMLElt($actesItems->Objet);

                $this->classification_date = Helpers :: getFromXMLElt($actesItems->ClassificationDateVersion);

                $classification_description = $actesClassificationCodesSQL->getDescription($this->get("authority_id"), $classification);
                $this->set("classification_string", $classification_description);


              // Fichier de l'acte
                $actePath = dirname($xmlFile) . "/" . Helpers :: getFromXMLElt($actesItems->Document->NomFichier);

                if (count($actesItems->Document->NomFichier) != 1) {
                    $this->errorMsg = "Impossible de traiter plusieurs document actes";
                    return false;
                }

                if (!$this->addActeFile($actePath, $actePath)) {
                    return false;
                }

                if (isset($actesItems->Document->Signature)) {
                    try {
                        $verifyPKCS7Signature = new VerifyPKCS7Signature(
                            RGS_VALIDCA_PATH,
                            new VerifyPemCertificateFactory(),
                            new PemCertificateFactory(),
                            new OpenSSLWrapper(
                                RGS_VALIDCA_PATH,
                                new CommandLauncher()
                            )
                        );
                        $verifyPKCS7Signature->verifySignature(
                            $actesItems->Document->Signature,
                            [],
                            $this->rootDir . "/" . $actePath
                        );
                    } catch (Exception $e) {
                        $this->errorMsg = $e->getMessage();
                        return false;
                    }
                    if (!$this->storeSign("acte", Helpers :: getFromXMLElt($actesItems->Document->Signature))) {
                        $this->errorMsg = "Erreur interne.";
                        return false;
                    }
                }

            // Fichiers pièces jointes
                if (isset($actesItems->Annexes)) {
                    foreach ($actesItems->Annexes->Annexe as $annexe) {
                        $attachmentPath = dirname($xmlFile) . "/" . Helpers :: getFromXMLElt($annexe->NomFichier);

                        if (!$this->addAttachmentFile($attachmentPath, $attachmentPath)) {
                            return false;
                        }

                        if (isset($annexe->Signature)) {
                            if (!$this->storeSign("attachment", Helpers :: getFromXMLElt($annexe->Signature))) {
                                  $this->errorMsg = "Erreur interne.";
                                  return false;
                            }
                        }
                    }
                }

                break;

            case "actes:ReponseCourrierSimple":
                $rep = $this->setDataFromCourrier(TypeTransaction::CourrierSimple, $xmlFile);
                break;

            case "actes:RefusPieceComplementaire":
                $rep = $this->setDataFromCourrier(TypeTransaction::DemandePieceComplementaire, $xmlFile, true);
                break;

            case "actes:PieceComplementaire":
                $rep = $this->setDataFromCourrier(TypeTransaction::DemandePieceComplementaire, $xmlFile, false);
                break;

            case "actes:RejetLettreObservations":
                $rep = $this->setDataFromCourrier(TypeTransaction::LettreDObservation, $xmlFile, true);
                break;

            case "actes:ReponseLettreObservations":
                $rep = $this->setDataFromCourrier(TypeTransaction::LettreDObservation, $xmlFile, false);
                break;

            case "actes:Annulation":
                $this->setType(TypeTransaction::Annulation);
                $acte_attr = $this->xmlObj->attributes($namespaces["actes"]);

                $this->unique_id = Helpers :: getFromXMLElt($acte_attr["IDActe"]);

                if (!$this->related_id = ActesTransaction :: getTransactionFromUniqueId($this->unique_id)) {
                    $this->errorMsg = "Transaction de référence introuvable.";
                    return false;
                }

                $related_trans = new ActesTransaction($this->related_id);
                if (!$related_trans->init()) {
                    $this->errorMsg = "Erreur d'initialisation de la transaction de référence.";
                    return false;
                }

                $this->number = $related_trans->get("number");

                break;

            case "actes:DemandeClassification":
                $this->setType(TypeTransaction::DemandeDeClassification);
                break;

            default:
                $this->errorMsg = "Mauvais type de transaction.";
                return false;
        }
        if ($rep == false) {
            return false;
        }

        return true;
    }

  /**
   * \brief Méthode d'ajout du fichier de l'acte
   * \param $name chaîne : Nom du fichier
   * \param $dest_name chaîne : Nom du fichier destination
   * \param $path chaîne (optionnel) : Chemin du fichier dans le système de fichier
   * \param $validate booléen (optionnel) : Procéder ou non à la validation du type de fichier
   * \return True en cas de succès, false sinon
   */
    public function addActeFile($name, $dest_name, $path = null, $validate = true, $code_pj = '')
    {
        if (!$this->addFile("acte", $name, $dest_name, $path, $validate, $code_pj)) {
            return false;
        }

        return true;
    }

  /**
   * \brief Méthode générique d'ajout d'un fichier
   * \param $type chaîne : Type de fichier à ajouter "acte" ou "attachment"
   * \param $name chaîne : Nom du fichier
   * \param $dest_name chaîne : Nom du fichier destination
   * \param $path chaîne : Chemin du fichier dans le système de fichier
   * \param $validate booléen (optionnel) : Procéder ou non à la validation du type de fichier
   * \return True en cas de succès, false sinon
   */
    public function addFile($type, $name, $dest_name, $path = false, $validate = true, $code_pj = '')
    {

        $ext = null;

      // Si le chemin n'est pas spécifié et les deux noms fournis identiques
      // c'est que le fichier se trouve déjà dans son emplacement définitif (dans le cas d'import de .tar.gz)
        $import = false;
        if (!$path && strcmp($name, $dest_name) == 0) {
            $path = $this->rootDir . "/" . $dest_name;
            $import = true;
        }

        if ($validate && $path) {
            if (!file_exists($path)) {
                $this->errorMsg = "Fichier non présent : " . basename($path);
                return false;
            }

            if (!$mimeType = @ mime_content_type($path)) {
                $this->errorMsg = "Erreur analyse mimetype.";
                return false;
            }

            $typeA = array('application/pdf' => 'pdf',
                        'application/xml' => 'xml',
                        'text/xml' => 'xml',
                        'image/jpeg' => 'jpg',
                        'image/png' => 'png',
            );
            if (isset($typeA[$mimeType])) {
                $ext = $typeA[$mimeType];
            } else {
                $ext = "";
            }


            if ($type == 'acte' && $this->isType(TypeTransaction::TransmissionActe)) {
                if (! in_array($ext, array('pdf','xml'))) {
                    $this->errorMsg = "Le fichier de l'acte \" " . basename($name) . " \" est de type \" " . $mimeType . " \". Fichier PDF ou XML requis.";
                    return false;
                }

                if ($ext == "xml") {
                    if ($this->nature_code != 5) {
                        $this->errorMsg = "Seuls les documents budgétaires et financiers peuvent être au format XML.";
                        return false;
                    }
                    if ($this->classif1 != 7 || $this->classif2 != 1) {
                        $this->errorMsg = "Seule la classification 7.1 est autorisée pour la transmission au format XML";
                        return false;
                    }
                }
            } elseif ($type == "acte") {
                if (! in_array($ext, array('pdf','jpg','png','xml'))) {
                    $this->errorMsg = "Le fichier de réponse \" " . basename($name) . " \" est de type \" " . $mimeType . " \". Fichier PDF, XML, PNG ou JPEG requis.";
                    return false;
                }
            } elseif ($type == "attachment") {
                if (! in_array($ext, array('pdf','jpg','png','xml'))) {
                    $this->errorMsg = "Le fichier attaché \" " . basename($name) . " \" est de type \" " . $mimeType . " \". Fichier PDF, XML, PNG ou JPEG requis.";
                    return false;
                }

                if ((isset($this->files["acte"]['mimetype']) && $this->files["acte"]['mimetype'] == 'application/xml') && (! in_array($ext, array('pdf')))) {
                    $this->errorMsg = "Les pièces jointes doivent être au format PDF avec un acte au format XML";
                    return false;
                }
            }

            if (!$size = @ filesize($path)) {
                $this->errorMsg = "Erreur détermination taille fichier.";
                return false;
            }

            $sha1 = sha1_file($path);
        }

        $new_name = $dest_name;

        if ($code_pj && ! preg_match("#^[A-Z0-9_]{5}$#", $code_pj)) {
            $this->errorMsg = "Le code de la PJ doit faire 5 caractères";
            return false;
        }

        if ($type == "acte") {
            if (! $import) {
                $new_name .= ".$ext";
            }

            $this->files["acte"] = array (
            "name" => $new_name,
            "posted_filename" => $name,
            "mimetype" => $mimeType,
            "size" => $size,
            "sha1" => $sha1,
            "code_pj" => $code_pj
            );
        } else {
            if (!$import) {
                if (!$ext) {
                    $ext = preg_replace("/^.*\.([^.]+)$/", "\${1}", $name);
                }

                $new_name .= "." . $ext;
            }

            $this->files["attachment"][] = array (
            "name" => $new_name,
            "posted_filename" => $name,
            "mimetype" => $mimeType,
            "size" => $size,
            "sha1" => $sha1,
            "code_pj" => $code_pj
            );
        }

      // Mise en place du fichier dans le répertoire de destination
        if (!$import) {
            if ($path) {
                if (!Helpers :: createDirTree(dirname($this->rootDir . "/" . $new_name))) {
                    $this->errorMsg = "Erreur système (createDirTree). Abandon";
                    return false;
                } else {
                    if (!copy($path, $this->rootDir . "/" . $new_name)) {
                        $this->errorMsg = "Erreur système (copy). Abandon";
                        return false;
                    }
                }
            }
        }

        return true;
    }

  /**
   * \brief Méthode d'ajout d'un fichier de pièce jointe
   * \param $name chaîne : Nom du fichier
   * \param $dest_name chaîne : Nom du fichier destination
   * \param $path chaîne (optionnel) : Chemin du fichier dans le système de fichier
   * \param $validate booléen (optionnel) : Procéder ou non à la validation du type de fichier
   * \return True en cas de succès, false sinon
   */
    public function addAttachmentFile($name, $dest_name, $path = null, $validate = true, $code_pj = '')
    {
        if (!$this->addFile("attachment", $name, $dest_name, $path, $validate, $code_pj)) {
            return false;
        }

        return true;
    }

    private function setDataFromCourrier(TypeTransaction $type, $xmlFile, $isRefus = false)
    {

        $this->setType($type);

        $namespaces = $this->xmlObj->getDocNamespaces();

        $actesItems = $this->xmlObj->children($namespaces["actes"]);

        $acte_attr = $this->xmlObj->attributes($namespaces["actes"]);

        $this->unique_id = Helpers :: getFromXMLElt($acte_attr["IDActe"]);
            $this->decision_date = Helpers :: getFromXMLElt($acte_attr["DateCourrierPref"]);

        if (!$this->related_transaction_id = ActesTransaction :: getTransactionFromUniqueId($this->unique_id)) {
            $this->errorMsg = "Transaction de référence introuvable.";
            return false;
        }

        $related_trans = new ActesTransaction($this->related_transaction_id);

        if (!$related_trans->init()) {
            $this->errorMsg = "Erreur d'initialisation de la transaction de référence.";
            return false;
        }

        $this->number = $related_trans->get("number");

        if ($type == TypeTransaction::DemandePieceComplementaire && !$isRefus) {
            foreach ($actesItems->Documents->Document as $fichiers) {
                $actePath = dirname($xmlFile) . "/" . Helpers :: getFromXMLElt($fichiers->NomFichier);
                if (!$this->addActeFile($actePath, $actePath)) {
                     return false;
                }
            }
        } else {
            $actePath = dirname($xmlFile) . "/" . Helpers :: getFromXMLElt($actesItems->Document->NomFichier);

            if (!$this->addActeFile($actePath, $actePath)) {
                 return false;
            }

            if (isset($actesItems->Document->Signature)) {
                if (!$this->storeSign("acte", Helpers :: getFromXMLElt($actesItems->Document->Signature))) {
                    $this->errorMsg = "Erreur interne.";
                    return false;
                }
            }
        }


        return true;
    }

  /**
   * \brief Méthode de récupération de l'identifiant d'une transaction d'après son unique_id
   * \return Un tableau de natures de transaction
   *
   * Commentaire : ce serait plus logique de renvoyer l'objet (EP 07/03/08)
   *
   */
    public static function getTransactionFromUniqueId($unique_id)
    {
        $db = DatabasePool::getInstance();
        $sql = "SELECT id FROM actes_transactions WHERE unique_id=" .
            $db->getPdo()->quote($unique_id) . " AND type='1'";

        $result = $db->select($sql);

        if (!$result->isError() && $result->num_row() == 1) {
            $row = $result->get_next_row();
            return $row["id"];
        }

      //On a pas trouvé, on va essayer dans les messages métier.
        $sql = "SELECT * FROM actes_included_files " .
            " WHERE filename=" . $db->getPdo()->quote($unique_id."_0.xml");

        $result = $db->select($sql);

        if (!$result->isError() && $result->num_row() == 1) {
            $row = $result->get_next_row();
            return $row["transaction_id"];
        }

        return false;
    }

  /**
   * \brief Méthode initialisant l'entité avec l'identifiant courant
   * \return true si succès, false sinon
  */
    public function init()
    {
        if (!parent :: init()) {
            return false;
        }

      // Traitement des codes matières
        $classif = explode(".", $this->classification);

        for ($i = 1; $i <= 5; $i++) {
            if (isset($classif[$i - 1])) {
                $cl = "classif" . $i;
                $this-> $cl = $classif[$i - 1];
            }
        }

        return true;
    }

  /**
   * \brief Méthode de suppression des différents fichiers associés à la transaction
   * \return True en cas de succès, false sinon
  */
    public function purgeFiles()
    {
      // Fichier XML de l'acte
        if (isset($this->xmlFileName) && !empty($this->xmlFileName)) {
            if (!unlink($this->rootDir . "/" . $this->xmlFileName)) {
                $this->errorMsg = "Ne peut supprimer un fichier temporaire.";
                return false;
            }
        }

      // Fichier de l'acte
        if (isset($this->files["acte"])) {
            if (!unlink($this->rootDir . "/" . $this->files["acte"]["name"])) {
                $this->errorMsg = "Ne peut supprimer un fichier temporaire.";
                return false;
            }
        }

      // Fichier des pièces jointes
        if (isset($this->files["attachment"])) {
            foreach ($this->files["attachment"] as $file) {
                if (mb_strlen($file["name"]) > 0) {
                    if (!unlink($this->rootDir . "/" . $file["name"])) {
                        $this->errorMsg = "Ne peut supprimer un fichier temporaire.";
                        return false;
                    }
                }
            }
        }

        return true;
    }

  /**
   * \brief Méthode de récupération de la liste des fichiers associés à cette transaction
   * \return Un tableau de description des fichiers
   */
    public function fetchFilesList()
    {
        if (isset($this->id) && count($this->files) <= 0) {
            $this->files = array ();

            if (!($this->files = ActesIncludedFile :: fetchFilesList($this->id))) {
                $this->errorMsg = "Erreur de récupération de la liste des fichiers.";
                $this->files = array ();
            }
        }

        return $this->files;
    }

  /**
   * \brief Méthode de récupération du cycle de vie de cette transaction
   * \return Un tableau contenant le workflow de la transaction
   */
    public function fetchWorkflow()
    {
        if (isset($this->id) && count($this->workflow) <= 0) {
            $sql = "SELECT id, status_id, date, message FROM actes_transactions_workflow WHERE transaction_id=? ORDER BY date, id ASC";

            $result = $this->db->select($sql, [$this->id]);

            if (!$result->isError()) {
                $this->workflow = $result->get_all_rows();
            }
        }

        return $this->workflow;
    }

  //Renvoie des informations sur les courriers de retour de la préfécture
  //Message 2, 3, 4 et 5

  /**
   * \brief Méthode de détermination si la transaction en cours a une demande d'annulation en cours
   * \return True si une demande d'annulation est en cours, false sinon
   */
    public function hasPendingCancelTrans()
    {
        if (isset($this->id)) {
            $sql = "SELECT id FROM actes_transactions WHERE actes_transactions.type='6' AND actes_transactions.related_transaction_id=" . $this->id;

            $result = $this->db->select($sql);

            if (!$result->isError() && $result->num_row() > 0) {
                while ($row = $result->get_next_row()) {
                    $cancel_trans = new ActesTransaction($row["id"]);

                    if (!$cancel_trans->init()) {
                        return false;
                    }

                    $status = $cancel_trans->getCurrentStatus();

                    if ($status > 0 && $status < 4) {
                        return true;
                    }
                }
            }
        }

        return false;
    }



  /**********************/
  /* Méthodes statiques */
  /**********************/

  /**
   * \brief Méthode d'enregistrement d'une transaction dans la base de données
   * \param $validate booléen (optionnel) Demande la validation ou non des données de l'entité avant enregistrement (true par défaut)
   * \return true si succès, false sinon
   */
    public function save($validate = true, $bouchon_4_strict_standard = true)
    {
        $new = false;
        if ($this->isNew()) {
            $new = true;
        }

        if ($this->isType(TypeTransaction::TransmissionActe)) {
            $done = false;
            $this->classification = "";
            $i = 1;
            while ($i <= 5 && !$done) {
                $var = "classif" . $i;
                if (isset($this-> $var) && !empty($this-> $var) && is_numeric($this-> $var)) {
                    $this->classification .= ($i > 1) ? "." . $this-> $var : $this-> $var;
                } else {
                  // Les deux premiers numéros de classification sont obligatoires
                    if ($i <= 2) {
                        $this->errorMsg = "Les deux premiers niveaux de classification sont obligatoires.";
                        return false;
                    } else {
                        $done = true;
                    }
                }

                $i++;
            }
        }

      // Si la transaction n'est pas une transmission d'acte on désactive
      // le contrôle des champs car tous les champs ne sont plus obligatoire
        if (!$this->isType(TypeTransaction::TransmissionActe)) {
            $validate = false;
        }

        $saveSQLRequest = parent::buildSaveSQLRequest($validate);

        if (! $saveSQLRequest->isValid()) {
            return false;
        }


        if (!$this->db->begin()) {
            $this->errorMsg = "Erreur lors de l'initialisation de la transaction.";
            return false;
        }
        if ($new && $this->get('authority_id')) {
            $sql_verif = "SELECT actes_transactions.id FROM actes_transactions " .
                " WHERE actes_transactions.number=? AND authority_id=?";

            if (
                $this->isType(TypeTransaction::TransmissionActe)
                &&
                $this->db->getOneValue($sql_verif, [$this->get('number'),$this->get('authority_id')])
            ) {
                $this->errorMsg = "Une transaction avec le même numéro existe déjà dans la base.";
                $this->db->rollback();
                return false;
            }
        }

        if (!$this->db->exec($saveSQLRequest->getRequest(), $saveSQLRequest->getParams())) {
            $this->errorMsg = "Erreur lors de la sauvegarde de la transaction.";
            $this->db->rollback();
            return false;
        }

        if ($new) {
          // Ajout de l'état initial
            if ($this->en_attente) {
                $result_set_status = $this->setNewStatus(ActesStatusSQL::STATUS_EN_ATTENTE_D_ETRE_POSTE, "Dépôt dans un état d'attente");
            } elseif ($this->is_en_attente_de_signature) {
                $result_set_status = $this->setNewStatus(ActesStatusSQL::STATUS_EN_ATTENTE_D_ETRE_SIGNEE, "En attente d'être signé");
            } else {
                $result_set_status = $this->setNewStatus(ActesStatusSQL::STATUS_POSTE, "Dépôt initial");
            }
            if (!$result_set_status) {
                $this->errorMsg = "Erreur lors de la définition de l'état initial de la transaction.";
                $this->db->rollback();
                return false;
            }

          // Ajout des fichiers inclus
            $files = array (
            array (
            "name" => $this->xmlFileName,
            "size" => $this->xmlFilesize,
            "mimetype" => "text/xml"
            )
            );

            if (isset($this->files["acte"])) {
                $files = array_merge($files, array (
                $this->files["acte"]
                ));
            }

            if (isset($this->files["attachment"])) {
                $files = array_merge($files, $this->files["attachment"]);
            }
            foreach ($files as $file) {
                if (empty($file['sha1'])) {
                    $file['sha1'] = "";
                }
                if (empty($file['code_pj'])) {
                    $file['code_pj'] = "";
                }

                $sql = "INSERT INTO actes_included_files (envelope_id, transaction_id, filename, posted_filename, filetype, filesize, signature,sha1,code_pj) VALUES(?,?,?,?,?,?,?,?,?)";

                $postedFilename = $file["posted_filename"] ?? "";
                $signature = $file["sign"] ?? "";
                $params = [
                    $this->envelope_id,
                    $this->id,
                    basename($file["name"]),
                    $postedFilename,
                    $file["mimetype"],
                    $file["size"],
                    $signature,
                    $file['sha1'],
                    $file['code_pj']
                ];

                if (!$this->db->exec($sql, $params)) {
                    $this->errorMsg = "Erreur lors de la journalisation des fichiers contenus dans l'archive.";
                    $this->db->rollback();
                    return false;
                }
            }
        }

        if (!$this->db->commit()) {
            $this->errorMsg = "Erreur lors de la validation de la transaction.";
            $this->db->rollback();
            return false;
        }

        return true;
    }

  /**
   * \brief Méthode qui positionne une transaction dans un état spécifié
   * \param $new_status_id entier : Identifiant du nouveau statut à positionner
   * \param $message chaîne : Message accompagnant le changement d'état
   * \return True en cas de succès, false sinon
   */
    public function setNewStatus($new_status_id, $message)
    {
        $date = date("Y-m-d H:i:s");
        $sql = "INSERT INTO actes_transactions_workflow (transaction_id, status_id, date, message) VALUES(?,?,?,?)";
        $params = [
            $this->id,
            $new_status_id,
            $date,
            $message
        ];

        if (!$this->db->exec($sql, $params)) {
            $this->errorMsg = "Erreur lors de la définition de l'état initial de la transaction.";
            return false;
        }

        $sql = "UPDATE actes_transactions SET last_status_id=$new_status_id " .
            " WHERE id=$this->id";
        $this->db->exec($sql);
        return true;
    }

  /**
   * \brief Méthode de suppression d'une transaction de la base de données
   * \param $id integer (optionnel) Numéro d'identifiant de la transaction, si non spécifié, entité en cours
   * \return true si succès, false sinon
  */
    public function delete($id = false)
    {
      // Efface l'entité spécifiée par $id ou alors l'entité courante si pas d'id
        if (!$id) {
            if (isset($this->id) && !empty($this->id)) {
                $id = $this->id;
            } else {
                $this->errorMsg = "Pas d'identifiant pour l'entité a supprimer";
                return false;
            }
        }

        if (!$this->db->begin()) {
            $this->errorMsg = "Erreur lors de l'initialisation de la transaction.";
            return false;
        }

        $sql = "DELETE FROM actes_included_files WHERE transaction_id=" . $id;

        if (!$this->db->exec($sql)) {
            $this->errorMsg = "Erreur lors de la suppression des fichiers reliés à la transaction.";
            $this->db->rollback();
            return false;
        }

        $sql = "DELETE FROM actes_transactions_workflow WHERE transaction_id=" . $id;

        if (!$this->db->exec($sql)) {
            $this->errorMsg = "Erreur lors de la suppression des états reliés à la transaction.";
            $this->db->rollback();
            return false;
        }

        if (!parent :: delete($id)) {
            $this->db->rollback();
            return false;
        }

        if (!$this->db->commit()) {
            $this->errorMsg = "Erreur lors de la validation de la transaction.";
            $this->db->rollback();
            return false;
        }

        return true;
    }

    public function getCourrierInfo()
    {
        $renvoie = array();
        $sql = "SELECT at1.id as id, at2.id as related_transaction_id,at1.type as type " .
            " FROM actes_transactions at1 LEFT JOIN actes_transactions at2 ON at1.id=at2.related_transaction_id  " .
            " WHERE at1.related_transaction_id=" . $this->id . " AND (at2.type != '6' OR at2.type IS NULL) ";

        $result = $this->db->select($sql);

        if ($result->isError()) {
            $this->errorMsg = "Impossible de récupere les transactions relatives.";
            return false;
        }

        while ($row = $result->get_next_row()) {
            if ($row["related_transaction_id"] == "") {
                $renvoie[$row["id"]] = array("type" => $row["type"],
                                    "sens" => "reçu");
            } else {
                $renvoie[$row["related_transaction_id"]] = array("type" => $row["type"],
                                    "sens" => "envoye");
            }
        }
        return $renvoie;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function canValidate()
    {

        if (ACTES_ALWAYS_CAN_VALIDATE) {
            return true;
        }
        assert(!!$this->id);
        $sql =  "SELECT date + interval '2 month' < now() as can_validate " .
                " FROM actes_transactions_workflow " .
                " WHERE transaction_id=" . $this->id .
                " AND status_id=4" ; //FIXME constante magique 4 dans la base de données....
        $result = $this->db->fetchAll($sql);
        if (! $result) {
            return false;
        }
        return $result[0]['can_validate'] == 't';
    }

    public function setEnAttente($en_attente)
    {
        $this->en_attente = $en_attente;
    }

    public function setEnAttenteDeSignature($is_en_attente_de_signature)
    {
        $this->is_en_attente_de_signature = $is_en_attente_de_signature;
    }

    public function isType(TypeTransaction $type): bool
    {
        return $this->getType() === $type;
    }

    public function setType(TypeTransaction $type)
    {
        $this->type = $type->value;
    }

    public function getType(): ?TypeTransaction
    {
        if (is_null($this->type)) {
            return null;
        }
        return TypeTransaction::tryFrom($this->type);
    }
}
