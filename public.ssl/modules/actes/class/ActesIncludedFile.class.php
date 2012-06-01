<?php


require_once(SITEROOT . "/class/DataObject.class.php");
require_once(SITEROOT . "/public.ssl/modules/actes/class/ActesEnvelope.class.php");

require_once("ActesNotification.class.php");


class ActesIncludedFile extends DataObject {
  
	protected $objectName = "actes_included_files";
  
  protected $envelope_id;
  protected $transaction_id;
  protected $filename;
  protected $posted_filename;
  protected $filetype;
  protected $filesize;
  protected $signature;

  protected $envelope;

  private $tampon;
  
  
  protected $dbFields = array( "envelope_id" => array( "descr" => "Identifiant enveloppe", "type" => "isInt", "mandatory" => true),
						 "transaction_id" => array( "descr" => "Identifiant transaction", "type" => "isInt", "mandatory" => false),
						 "filename" => array( "descr" => "Nom du fichier métier", "type" => "isString", "mandatory" => true),
						 "posted_filename" => array( "descr" => "Nom du fichier original", "type" => "isString", "mandatory" => false),
						 "filetype" => array( "descr" => "Type du fichier", "type" => "isString", "maxlength" => 499, "mandatory" => true),
						 "filesize" => array( "descr" => "Taille du fichier", "type" => "isInt", "mandatory" => true),
						 "signature" => array( "descr" => "Signature électronique du fichier", "type" => "isString", "mandatory" => false)
						 );

  /**
   * \brief Constructeur
   * \param id integer Numéro d'identifiant d'un fichier attaché
   */
  public function __construct($id = false) {
	parent::__construct($id);

	if ($id) {
	  $this->init();
	  $this->initEnvelope();
	}
	
		$this->tampon = false;
	
  }

  /**
   * \brief Méthode d'initialisation de l'enveloppe contenant le fichier courant
   */
  public function initEnvelope() {
	if (isset($this->envelope_id)) {
	  $this->envelope = new ActesEnvelope($this->envelope_id);
	  $this->envelope->init();
	}
  }

  
  public function setTampon(){
  	$this->tampon = true;
  }
  
  /**
   * \brief Méthode qui renvoie le fichier au navigateur
   */
  public function sendFile() {
	if (isset($this->filename)) {
	  // Il faut extraire le fichier demandé dans un stockage temporaire
	  $tmpDir = "/tmp/" . Helpers::genTempName();

	  if (! @mkdir($tmpDir)) {
		$this->errorMsg = "Erreur système de fichiers";
		return false;
	  }
	  
	  if (! file_exists(ACTES_FILES_UPLOAD_ROOT . '/' . $this->envelope->get("file_path"))) {
		$this->errorMsg = "Le fichier archive n'est pas/plus disponible.";
		return false;
	  }

	  $cmd = 'tar xzf ' . ACTES_FILES_UPLOAD_ROOT . '/' . $this->envelope->get("file_path") . " -C " . $tmpDir . " " . $this->filename;
	  
	  //$status = system($cmd, $ret);
	  Trace::wrap_exec($cmd, $status, $ret);

	  $ret_value = true;

	  if ($status === false || $ret != 0) {
		$this->errorMsg = "Erreur d'extraction du fichier demandé (code " . $ret . ")";
		$ret_value = false;
	  } else {
		if (strlen($this->posted_filename)) {
		  $browserName = $this->posted_filename;
		} else {
		  $browserName = $this->filename;
		}

                $pdftkise='/tmp/' .$this->filename;
		$path_parts = pathinfo($this->filename);
		
		//FIXME SALE 
		if ($path_parts['extension'] == 'pdf' && $this->tampon){
				
			$cmdpdftk='pdftk '. $tmpDir . '/' .$this->filename." stamp ".SITEROOT."/data-exemple/vide.pdf output ".$pdftkise;
                        Trace::wrap_exec($cmdpdftk, $status, $ret);
                        if ($status === false || $ret != 0) {
                                $this->errorMsg = "Erreur lors de la convertion via pdftk (code " . $ret . ")";
                                $ret_value = false;
                        }
                        else{
                            $transactionId = $this->get("transaction_id");
                            $actesNotification = new ActesNotification($this->db);
                            $transactionInfo = $actesNotification->getTransactionInfo($transactionId);
                            set_include_path(SITEROOT."/ext/" . PATH_SEPARATOR .   get_include_path());
                            require_once(SITEROOT."/class/TamponPDF.class.php");

                            try {	
                                    $pdf = Zend_Pdf::load($pdftkise);

                                    $tampon = new TamponPDF($pdf);
                                    $tampon->setText(array("Envoyé en préfecture le ".date("d/m/Y",strtotime($transactionInfo['submission_date'])),
                                    "Reçu en préfecture le ".date("d/m/Y",strtotime($transactionInfo['date'])),
                                    "Affiché le " ));
                                    $tampon->setNameFile($this->filename);
                                    $tampon->render();
                            } catch (Exception $e){
                                    Helpers::sendFileToBrowser($pdftkise, $browserName, $this->filetype);
                            }
                        }
		} elseif (! Helpers::sendFileToBrowser($tmpDir . "/" . $this->filename, $browserName, $this->filetype)) {
		  $this->errorMsg = "Erreur envoi fichier";
		  $ret_value = false;
		}

		// Suppression du fichier
		if (! unlink($tmpDir . "/" . $this->filename)) {
		  $this->errorMsg = "Erreur suppression fichier";
		  $ret_value = false;
		}
                
                if (! unlink($pdftkise)) {
                  $this->errorMsg = "Erreur suppression du fichier modifie par pdftk";
                  $ret_value = false;
                }
	  }

	  // Suppression du répertoire temporaire
	  if (! rmdir($tmpDir)) {
		$this->errorMsg = "Erreur suppression répertoire temporaire";
		$ret_value = false;
	  }

	  return $ret_value;
	}
  }

  /**********************/
  /* Méthodes statiques */
  /**********************/

  /**
   * \brief Méthode de récupération de la liste des fichiers associés à une transaction
   * \param id entier : identifiant de la transaction
   * \return Un tableau de description des fichiers
   */
  static public function fetchFilesList($id) {
	if (isset($id)) {
	  $sql = "SELECT id, filename AS name, posted_filename, filetype AS mimetype, filesize AS size, signature AS sign FROM actes_included_files WHERE transaction_id=" . $id . " ORDER BY id";
	  
	  
	  $db =& DatabasePool::getInstance();

	  $result = $db->select($sql);

	  $files = array();

	  if (! $result->isError()) {
		$files = $result->get_all_rows();
	  }
	}

	return $files;
  }
}
?>
