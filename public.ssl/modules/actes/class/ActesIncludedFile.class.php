<?php

use Psr\Log\LoggerInterface;
use S2lowLegacy\Class\actes\ActeTamponne;
use S2lowLegacy\Class\DatabasePool;
use S2lowLegacy\Class\DataObject;
use S2lowLegacy\Class\Helpers;

class ActesIncludedFile extends DataObject
{
    protected $objectName = "actes_included_files";

    protected $envelope_id;
    protected $transaction_id;
    protected $filename;
    protected $posted_filename;
    protected $filetype;
    protected $filesize;
    protected $signature;

    protected $code_pj;

    /** @var  ActesEnvelope */
    protected $envelope;

    private $tampon;
    private $date_affichage;
    private readonly LoggerInterface $logger;


    protected $dbFields = array("envelope_id" => array("descr" => "Identifiant enveloppe", "type" => "isInt", "mandatory" => true),
        "transaction_id" => array("descr" => "Identifiant transaction", "type" => "isInt", "mandatory" => false),
        "filename" => array("descr" => "Nom du fichier métier", "type" => "isString", "mandatory" => true),
        "posted_filename" => array("descr" => "Nom du fichier original", "type" => "isString", "mandatory" => false),
        "filetype" => array("descr" => "Type du fichier", "type" => "isString", "maxlength" => 499, "mandatory" => true),
        "filesize" => array("descr" => "Taille du fichier", "type" => "isInt", "mandatory" => true),
        "signature" => array("descr" => "Signature électronique du fichier", "type" => "isString", "mandatory" => false),
        "code_pj" => array("descr" => "Code de la PJ", "type" => "isString", "mandatory" => false)
    );


    /**
     * Méthode de récupération de la liste des fichiers associés à une transaction
     * @param $id int identifiant de la transaction
     * @return array Un tableau de description des fichiers
     * @throws Exception
     */
    public static function fetchFilesList($id)
    {
        $files = array();
        if (isset($id)) {
            $sql = "SELECT id, filename AS name, posted_filename, filetype AS mimetype, filesize AS size, signature AS sign, code_pj FROM actes_included_files WHERE transaction_id=" . $id . " ORDER BY id";


            $db = DatabasePool::getInstance();

            $result = $db->select($sql);

            $files = array();

            if (!$result->isError()) {
                $files = $result->get_all_rows();
            }
        }

        return $files;
    }

    /**
     * ActesIncludedFile constructor.
     * @param bool $id
     */
    public function __construct($id = false)
    {
        parent::__construct($id);

        if ($id) {
            $this->init();
            $this->initEnvelope();
        }

        $this->tampon = false;
        $this->logger = \S2lowLegacy\Lib\ObjectInstancierFactory::getObjetInstancier()->get(LoggerInterface::class);
    }

    /**
     * \brief Méthode d'initialisation de l'enveloppe contenant le fichier courant
     */
    public function initEnvelope()
    {
        if (isset($this->envelope_id)) {
            $this->envelope = new ActesEnvelope($this->envelope_id);
            $this->envelope->init();
        }
    }


    public function setTampon($date_affichage = false)
    {
        $this->tampon = true;
        $this->date_affichage = $date_affichage;
    }

    /**
     * \brief Méthode qui renvoie le fichier au navigateur
     */
    public function sendFile()
    {
        if (isset($this->filename)) {
            // Il faut extraire le fichier demandé dans un stockage temporaire
            $tmpDir = "/tmp/" . Helpers::genTempName();

            $this->errorMsg = '';
            if (!@mkdir($tmpDir)) {
                $this->errorMsg .= "Erreur système de fichiers";
                return false;
            }

            $enveloppeId = $this->envelope->get("id");
            $objectInstancier = \S2lowLegacy\Lib\ObjectInstancierFactory::getObjetInstancier();
            $actesRetriever = $objectInstancier->get('app.localFileResolver.acte_enveloppe');
            $envelope_path = $actesRetriever->getFullPath($enveloppeId);
            $objectInstancier->get('app.store.file.acte_enveloppe')->downloadFileFromCloud($enveloppeId);

            if (!file_exists($envelope_path)) {
                $this->errorMsg .= "Le fichier archive n'est pas/plus disponible.";
                return false;
            }

            $cmd = 'tar xzf ' . $envelope_path . " -C " . $tmpDir . " " . $this->filename;

            exec($cmd, $status, $ret);

            $output_str = implode("\n", $status);

            $this->logger->debug(
                sprintf(
                    "Execution de: %s- résultat : %s - output : %s",
                    $cmd,
                    $ret,
                    $output_str
                )
            );

            $ret_value = true;

            if ($status === false || $ret != 0) {
                $this->errorMsg .= "Erreur d'extraction du fichier demandé (code " . $ret . ")";
                $ret_value = false;
            } else {
                if (mb_strlen($this->posted_filename)) {
                    $browserName = $this->posted_filename;
                } else {
                    $browserName = $this->filename;
                }

                $path_parts = pathinfo($this->filename);

                //FIXME SALE
                if ($path_parts['extension'] == 'pdf' && $this->tampon) {
                    $pathpdforig = $tmpDir . '/' . $this->filename;

                    $acteTamponne = $objectInstancier->get(ActeTamponne::class);
                    $acteTamponne->render($pathpdforig, $this->get("transaction_id"), $this->date_affichage);
                } elseif (!Helpers::sendFileToBrowser($tmpDir . "/" . $this->filename, $browserName, $this->filetype)) {
                    $this->errorMsg .= "Erreur envoi fichier";
                    $ret_value = false;
                }
                if (file_exists($tmpDir . "/" . $this->filename)) {
                    if (!unlink($tmpDir . "/" . $this->filename)) {
                        $this->errorMsg .= "Erreur suppression fichier";
                        $ret_value = false;
                    }
                }
            }

            // Suppression du répertoire temporaire
            if (file_exists($tmpDir)) {
                if (!rmdir($tmpDir)) {
                    $this->errorMsg .= "Erreur suppression répertoire temporaire";
                    $ret_value = false;
                }
            }//fin if test dossier $tmpDir

            return $ret_value;
        }
        return false;
    }
}
