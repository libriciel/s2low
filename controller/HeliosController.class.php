<?php

require_once(SITEROOT . '/public.ssl/modules/helios/class/HeliosTransaction.class.php');
require_once(SITEROOT . '/public.ssl/modules/helios/class/HeliosTransactionWorkflow.class.php');

class HeliosController extends Controller {

	public function import($userId, User $me, Module $module){

		$must_signed = Helpers::getVarFromPost("must_signed",true);

		$uploaddir = HELIOS_FILES_UPLOAD_ROOT;
		$uploadFile_baseName = $_FILES['enveloppe']['name'];

		$temporary_name = time().mt_rand(0, mt_getrandmax());
		$uploadfile = $uploaddir . $temporary_name;

		if (! move_uploaded_file_wrapper($_FILES['enveloppe']['tmp_name'], $uploadfile)) {
			throw new Exception("Échec lors du téléchargement du fichier");
		}

		if (!Antivirus::checkArchiveSanity($uploadfile)) {
			throw new Exception(Antivirus::$errorMsg);
		}

		$SHA1 = sha1_file($uploadfile);

		$ht = new HeliosTransaction();
		$htw = new HeliosTransactionWorkflow();
		$file_size=$_FILES['enveloppe']['size'];

		if ($file_size>HELIOS_MAX_UPLOAD_SIZE) {
			$message = "Taille de fichier supérieur à la limite autorisée (". (HELIOS_MAX_UPLOAD_SIZE/1024/1024)."Mo maximum).";
			throw new Exception($message);
		}

		$submission_date=date("Y-m-d H:i:s");;
		$ht->set("filename", $uploadFile_baseName);
		$ht->set("user_id", $userId);
		$ht->set("authority_id",$me->get("authority_id"));
		$ht->set("file_size",$file_size);
		$ht->set("submission_date",$submission_date);
		$ht->set("sha1",$SHA1);

		$myAuthority = new Authority($me->get("authority_id"));
		$siren=$myAuthority->get('siren');

		$ext_siret=$myAuthority->get('ext_siret');
		$ht->set("siren",$siren.$ext_siret);

		if ($ht->CheckDuplicate()== true) {
			unlink($uploadfile);
			throw new Exception("doublon détecté. Ce fichier a déjà été posté.");
		}

		rename($uploadfile,$uploaddir.$SHA1);
		chmod($uploaddir.$SHA1, 0644);

		$R = $ht->save(true);

		if (!$R) {
			$message = "Erreur de l'initialisaton de l'accès à la table helios_transactions.";
			if (!Log :: newEntry(LOG_ISSUER_NAME, $message, 3, false, 'USER', $module->get("name"), $me)) {
				$message .= "\nErreur de journalisation.";
			}
			throw new Exception($message);
		}


		$id_transaction = $ht->getId();

		$htw->set("transaction_id", $id_transaction);
		if ($must_signed){
			$htw->set("status_id", 13);
			$htw->set("message", "Fichier en attente d'être signé");
		} elseif(! $me->checkDroit($module->get("name"),'TT')) {
			$htw->set("status_id", 14);
			$htw->set("message", "Fichier en attente d'être télétransmis");

		} else {
			$htw->set("status_id", 1);
			$htw->set("message", "Fichier bien reçu par la plate-forme S2low");
		}
		$htw->set("date", date('Y-m-d H:i:s'));

		if (!$htw->save(true)) {
			$message = "Erreur de l'initialisaton de l'accès à la table helios_transactions_workflow.";
			if (!Log :: newEntry(LOG_ISSUER_NAME, $message, 3, false, 'USER', $module->get("name"), $me)) {
				$message .= "\nErreur de journalisation.";
			}
			throw new Exception($message);
		}


		$heliosTransactionSQL = new HeliosTransactionsSQL($this->getSQLQuery());
		$heliosTransactionSQL->setLastStatusId($id_transaction);

		$msg = "Création de la transation n°" . $id_transaction . ". Résultat ok.";
		if (!Log :: newEntry(LOG_ISSUER_NAME, $msg, 1, false, 'USER', $module->get("name"), $me)) {
			//FIXME
		}

		return $id_transaction;
	}

	public function importAction(){
		$module = new Module();
		if (!$module->initByName("helios")) {
			$this->redirectSSL(WEBSITE_SSL,"Erreur d'initialisation du module");
		}

		$me = new User();
		if (!$me->authenticate()) {
			$this->redirectSSL(WEBSITE,"Échec de l'authentification");
		}

		$userId = $me->getId();

		if (!$module->isActive() || !$me->checkDroit($module->get("name"),'CS')) {
			$this->redirectSSL(WEBSITE_SSL,"Accès refusé");
		}

		$id_transaction = false;

		try {
			$id_transaction = $this->import($userId, $me, $module);
		} catch (Exception $e){
			Helpers :: returnAndExit(1, $e->getMessage(), WEBSITE_SSL . "/modules/helios/helios_fichier_import.php");
		}

		$msg = "Création de la transation n°" . $id_transaction . ". Résultat ok.";
		Helpers :: returnAndExit(0,$msg, WEBSITE_SSL . "/modules/helios/helios_transac_show.php?id=" . $id_transaction);
	}

	public function importAPIAction(){
		$module = new Module();
		if (!$module->initByName("helios")) {
			echo "KO\nErreur d'initialisation du module";
			exit ();
		}

		$me = new User();
		if (! $me->authenticate()) {
			echo "KO\nÉchec de l'authentification";
			exit();
		}

		$userId = $me->getId();

		if (!$module->isActive() || !$me->canAccess($module->get("name"))) {
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
			$id_transaction = $this->import($userId, $me, $module);
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