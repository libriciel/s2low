<?php
/*
 * TéDéTIS - Copyright 2006 Alternance-Soft
 * Contributeur : Jérôme Schell, Août 2006 
 *
 * contact@alternancesoft.com
 *
 * Ce logiciel est un programme informatique servant à la
 * dématèrialisation de l'administration. 
 *
 * Ce logiciel est régi par la licence CeCILL soumise au droit français et
 * respectant les principes de diffusion des logiciels libres. Vous pouvez
 * utiliser, modifier et/ou redistribuer ce programme sous les conditions
 * de la licence CeCILL telle que diffusée par le CEA, le CNRS et l'INRIA 
 * sur le site "http://www.cecill.info".
 *
 * En contrepartie de l'accessibilité au code source et des droits de copie,
 * de modification et de redistribution accordés par cette licence, il n'est
 * offert aux utilisateurs qu'une garantie limitée.  Pour les mêmes raisons,
 * seule une responsabilité restreinte pèse sur l'auteur du programme,  le
 * titulaire des droits patrimoniaux et les concédants successifs.
 *
 * A cet égard  l'attention de l'utilisateur est attirée sur les risques
 * associés au chargement,  à l'utilisation,  à la modification et/ou au
 * développement et à la reproduction du logiciel par l'utilisateur étant 
 * donné sa spécificité de logiciel libre, qui peut le rendre complexe à 
 * manipuler et qui le réserve donc à des développeurs et des professionnels
 * avertis possédant  des  connaissances  informatiques approfondies.  Les
 * utilisateurs sont donc invités à charger  et  tester  l'adéquation  du
 * logiciel à leurs besoins dans des conditions permettant d'assurer la
 * sécurité de leurs systèmes et ou de leurs données et, plus généralement, 
 * à l'utiliser et l'exploiter dans les mêmes conditions de sécurité. 
 *
 * Le fait que vous puissiez accéder à cet en-tête signifie que vous avez 
 * pris connaissance de la licence CeCILL, et que vous en avez accepté les
 * termes.
*/
?>
<?php
/**
 * \class ActesIncludedFile ActesIncludedFile.class.php
 * \brief Cette classe permet de gérer les fichiers reliés aux transactions
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 29.01.2007
 * 
 *
 * Cette classe fournit des méthodes de gestion des transactions
 * Actes
 *
 * Modifications :
 * Auteur   Date       Commentaire
 * PEV		16-06-2010  modification de la ligne 167 : la date d'émission doit être celle de transmission et non de décision
 */

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

		$path_parts = pathinfo($this->filename);
		
		//FIXME SALE 
		if ($path_parts['extension'] == 'pdf' && $this->tampon){
				
			
			$transactionId = $this->get("transaction_id");
			$actesNotification = new ActesNotification($this->db);
			$transactionInfo = $actesNotification->getTransactionInfo($transactionId);
			set_include_path(SITEROOT."/ext/" . PATH_SEPARATOR .   get_include_path());
			require_once(SITEROOT."/class/TamponPDF.class.php");
			
			try {	
				$pdf = Zend_Pdf::load($tmpDir . "/" .$this->filename);
	
				$tampon = new TamponPDF($pdf);
				$tampon->setText(array("Envoyé en préfecture le ".date("d/m/Y",strtotime($transactionInfo['submission_date'])),
				"Reçu en préfécture le ".date("d/m/Y",strtotime($transactionInfo['date'])),
				"Affiché le " ));
				$tampon->setNameFile($this->filename);
				$tampon->render();
			} catch (Exception $e){
				Helpers::sendFileToBrowser($tmpDir . "/" . $this->filename, $browserName, $this->filetype);
			}
		
		} else 	if (! Helpers::sendFileToBrowser($tmpDir . "/" . $this->filename, $browserName, $this->filetype)) {
		  $this->errorMsg = "Erreur envoi fichier";
		  $ret_value = false;
		}

		// Suppression du fichier
		if (! unlink($tmpDir . "/" . $this->filename)) {
		  $this->errorMsg = "Erreur suppression fichier";
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
