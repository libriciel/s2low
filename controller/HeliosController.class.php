<?php

require_once(SITEROOT . '/public.ssl/modules/helios/class/HeliosTransaction.class.php');
require_once(SITEROOT . '/public.ssl/modules/helios/class/HeliosTransactionWorkflow.class.php');

class HeliosController extends Controller {

	const MODULE_NAME = 'helios';

	private $helios_max_upload_size;

	public function __construct(ObjectInstancier $objectInstancier){
		parent::__construct($objectInstancier);
		$this->setHeliosMaxUploadSize(HELIOS_MAX_UPLOAD_SIZE);
	}

	public function setHeliosMaxUploadSize($helios_max_upload_size){
		$this->helios_max_upload_size = $helios_max_upload_size;
	}

	public function import($userId, User $me){
		$file_size = $_FILES['enveloppe']['size'];
		if ($file_size > $this->helios_max_upload_size) {
			$message = "Taille de fichier supérieur à la limite autorisée (".
				($this->helios_max_upload_size/1024/1024)." Mo maximum).";
			throw new Exception($message);
		}

		$must_signed = Helpers::getVarFromPost("must_signed",true);

		$uploaddir = HELIOS_FILES_UPLOAD_ROOT;

		try {
			$uploadFile_baseName = $_FILES['enveloppe']['name'];

			$temporary_name = time().mt_rand(0, mt_getrandmax());
			$uploadfile = $uploaddir . $temporary_name;

			if (!move_uploaded_file_wrapper($_FILES['enveloppe']['tmp_name'], $uploadfile)) {
				throw new Exception("Échec lors du téléchargement du fichier");
			}
		} catch (Exception $e){
			throw new Exception("Échec lors du téléchargement du fichier");
		}

		$heliosTransactionSQL = new HeliosTransactionsSQL($this->getSQLQuery());
		$SHA1 = sha1_file($uploadfile);

		if ($heliosTransactionSQL->isDuplicate($SHA1)) {
			unlink($uploadfile);
			throw new Exception("doublon détecté. Ce fichier a déjà été posté.");
		}

		if (!Antivirus::checkArchiveSanity($uploadfile)) {
			throw new Exception(Antivirus::$errorMsg);
		}


		$myAuthority = new Authority($me->get("authority_id"));
		$siren=$myAuthority->get('siren');
		$submission_date=date("Y-m-d H:i:s");;

		$ext_siret=$myAuthority->get('ext_siret');

		$ht = new HeliosTransaction();
		$ht->set("filename", $uploadFile_baseName);
		$ht->set("user_id", $userId);
		$ht->set("authority_id",$me->get("authority_id"));
		$ht->set("file_size",$file_size);
		$ht->set("submission_date",$submission_date);
		$ht->set("sha1",$SHA1);
		$ht->set("siren",$siren.$ext_siret);



		rename($uploadfile,$uploaddir.$SHA1);
		chmod($uploaddir.$SHA1, 0644);

		$R = $ht->save(true);

		if (!$R) {
			$message = "Erreur de l'initialisaton de l'accès à la table helios_transactions.";
			if (!Log :: newEntry(LOG_ISSUER_NAME, $message, 3, false, 'USER', self::MODULE_NAME, $me)) {
				$message .= "\nErreur de journalisation.";
			}
			throw new Exception($message);
		}


		$id_transaction = $ht->getId();

		if ($must_signed){
			$state = HeliosTransactionsSQL::ATTENTE_SIGNEE;
			$message = "Fichier en attente d'être signé";
		} elseif(! $me->checkDroit(self::MODULE_NAME,'TT')) {
			$state = HeliosTransactionsSQL::ATTENTE_POSTEE;
			$message = "Fichier en attente d'être télétransmis";
		} else {
			$state = HeliosTransactionsSQL::POSTE;
			$message = "Fichier bien reçu par la plate-forme S2low";
		}
		$heliosTransactionSQL->updateStatus($id_transaction,$state,$message);

		$msg = "Création de la transation n°" . $id_transaction . ". Résultat ok.";
		if (!Log :: newEntry(LOG_ISSUER_NAME, $msg, 1, false, 'USER', self::MODULE_NAME, $me)) {
			//FIXME
		}

		return $id_transaction;
	}

	public function importAction(){
		$module = new Module();
		if (!$module->initByName(self::MODULE_NAME)) {
			$this->redirectSSL(WEBSITE_SSL,"Erreur d'initialisation du module");
		}

		$me = new User();
		if (!$me->authenticate()) {
			$this->redirectSSL(WEBSITE,"Échec de l'authentification");
		}

		$userId = $me->getId();

		if (!$module->isActive() || !$me->checkDroit(self::MODULE_NAME,'CS')) {
			$this->redirectSSL(WEBSITE_SSL,"Accès refusé");
		}

		$id_transaction = false;

		try {
			$id_transaction = $this->import($userId, $me);
		} catch (Exception $e){
			Helpers :: returnAndExit(1, $e->getMessage(), WEBSITE_SSL . "/modules/helios/helios_fichier_import.php");
		}

		$msg = "Création de la transation n°" . $id_transaction . ". Résultat ok.";
		Helpers :: returnAndExit(0,$msg, WEBSITE_SSL . "/modules/helios/helios_transac_show.php?id=" . $id_transaction);
	}

	public function importAPIAction(){
		$module = new Module();
		if (!$module->initByName(self::MODULE_NAME)) {
			echo "KO\nErreur d'initialisation du module";
			exit ();
		}

		$me = new User();
		if (! $me->authenticate()) {
			echo "KO\nÉchec de l'authentification";
			exit();
		}

		$userId = $me->getId();

		if (!$module->isActive() || !$me->canAccess(self::MODULE_NAME)) {
			echo "KO\nAccès refusé";
			exit();
		}

		$doc = new DOMDocument();
		$doc->formatOutput = true;
		$doc->preserveWhiteSpace = false;
		$root=$doc->createElement("import");
		$doc->appendChild($root);
		$idElement=$doc->createElement("id");
		$resultatElement=$doc->createElement("resultat");
		$messageElement=$doc->createElement("message");

		$root->appendChild($idElement);
		$root->appendChild($resultatElement);
		$root->appendChild($messageElement);

		try{
			$id_transaction = $this->import($userId, $me);
			$msg = "Téléchargement du fichier réussi.";
			$idElement->appendChild( $doc->createTextNode($id_transaction));
			$resultatElement->appendChild( $doc->createTextNode("OK"));
			$messageElement->appendChild( $doc->createTextNode( utf8_encode($msg)));
		} catch (Exception $e) {
			$resultatElement->appendChild( $doc->createTextNode( "KO" ) );
			$messageElement->appendChild( $doc->createTextNode( utf8_encode($e->getMessage())));
		}


		$xmlFile=HELIOS_FILES_ROOT."/temp/import-".uniqid().".xml";
		$doc->save($xmlFile);

		if (!Helpers::sendFileToBrowser($xmlFile, "import.xml", "text/xml")) {
			echo "KO\nimpossible d'envoyer le fichier XML";
		}
		unlink($xmlFile);

	}

}