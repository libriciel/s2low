<?php

require_once(SITEROOT . '/public.ssl/modules/helios/class/HeliosTransaction.class.php');
require_once(SITEROOT . '/public.ssl/modules/helios/class/HeliosTransactionWorkflow.class.php');
require_once (SITEROOT . '/public.ssl/modules/helios/class/HeliosRetour.class.php');

class HeliosController extends Controller {

	const MODULE_NAME = 'helios';

	private $helios_max_upload_size;

	public function __construct(ObjectInstancier $objectInstancier){
		parent::__construct($objectInstancier);
		$this->setHeliosMaxUploadSize(HELIOS_MAX_UPLOAD_SIZE);
	}

    /**
     * @return PesAllerRetriever
     */
	private function getPesAllerRetriever(){
        return $this->getObjectInstancier()->get("PesAllerRetriever");
    }

	public function setHeliosMaxUploadSize($helios_max_upload_size){
		$this->helios_max_upload_size = $helios_max_upload_size;
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
			$id_transaction = $this->import($userId);
		} catch (Exception $e){
			Helpers :: returnAndExit(1, $e->getMessage(), WEBSITE_SSL . "/modules/helios/helios_fichier_import.php");
		}

		$msg = "Création de la transation n°" . $id_transaction . ". Résultat ok.";
		Helpers :: returnAndExit(0,$msg, WEBSITE_SSL . "/modules/helios/helios_transac_show.php?id=" . $id_transaction);
	}

	public function import($user_id){

		/** @var RgsConnexion $rgsConnexion */
		$rgsConnexion = $this->getObjectInstancier()->{'RgsConnexion'};
		if (! $rgsConnexion->isRgsConnexion()){
			throw new Exception("Votre certificat n'est pas RGS et ne vous permet donc pas de télétransmettre");
		}

		if (empty($_FILES['enveloppe'])){
		    throw new Exception("Aucune enveloppe trouvée : la taille de l'enveloppe dépasse probablement la taille maximum");
        }

		if ($_FILES['enveloppe']['error'] != UPLOAD_ERR_OK){
		    throw new Exception(
		        "Erreur lors du téléchargement du fichier : code {$_FILES['enveloppe']['error']}");
        }


		$file_size = $_FILES['enveloppe']['size'];
		if ($file_size > $this->helios_max_upload_size) {
			$message = "Taille de fichier supérieure à la limite autorisée (".
				($this->helios_max_upload_size/1024/1024)." Mo maximum).";
			throw new Exception($message);
		}

		$heliosTransactionSQL = new HeliosTransactionsSQL($this->getSQLQuery());
		$SHA1 = sha1_file($_FILES['enveloppe']['tmp_name']);

		if ($heliosTransactionSQL->isDuplicate($SHA1)) {
			throw new Exception("doublon détecté. Ce fichier a déjà été posté.");
		}

        $pes_aller_destination = $this->getPesAllerRetriever()->getPathForNonExistingFile($SHA1);
		try {
			$pes_aller_original_name = $_FILES['enveloppe']['name'];
			if (!move_uploaded_file_wrapper($_FILES['enveloppe']['tmp_name'], $pes_aller_destination)) {
				throw new Exception("Échec lors du téléchargement du fichier");
			}
			chmod($pes_aller_destination, 0644);
		} catch (Exception $e){
			throw new Exception("Échec lors du téléchargement du fichier");
		}
		return $this->importFile($user_id,$pes_aller_destination,$pes_aller_original_name);
	}

	public function importFile($user_id,$filepath,$original_filename){
		$heliosTransactionSQL = new HeliosTransactionsSQL($this->getSQLQuery());

		$userSQL = new UserSQL($this->getSQLQuery());
		$user_info = $userSQL->getInfo($user_id);

		$authoritySQL = new AuthoritySQL($this->getSQLQuery());
		$authority_info = $authoritySQL->getInfo($user_info['authority_id']);

		$moduleSQL = new ModuleSQL($this->getSQLQuery());
		$module_info = $moduleSQL->getInfoByName(self::MODULE_NAME);

		$must_signed = Helpers::getVarFromPost("must_signed",true);

		$siren = $authority_info['siren'];
		$ext_siret = $authority_info['ext_siret'];

		$filesize = filesize($filepath);
		$sha1 = sha1_file($filepath);
		$id_transaction = $heliosTransactionSQL->create($original_filename,$sha1,$user_id,$user_info['authority_id'],$filesize,$siren.$ext_siret);

		if ($must_signed){
			$state = HeliosTransactionsSQL::ATTENTE_SIGNEE;
			$message = "Fichier en attente d'être signé";
		} elseif(! $moduleSQL->hasDroit($module_info['id'],$user_id,'TT')) {
			$state = HeliosTransactionsSQL::ATTENTE_POSTEE;
			$message = "Fichier en attente d'être télétransmis";
		} else {
			$state = HeliosTransactionsSQL::POSTE;
			$message = "Fichier bien reçu par la plate-forme S2low";
		}
		$heliosTransactionSQL->updateStatus($id_transaction,$state,$message);

		$msg = "Création de la transation n°" . $id_transaction . ". Résultat ok.";
		Log :: newEntry(LOG_ISSUER_NAME, $msg, 1, false, 'USER', self::MODULE_NAME, false,$user_id);
		return $id_transaction;
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
			$id_transaction = $this->import($userId);
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
		$this->controller_exit();
	}

	public function updateSiretFromPESAller($min_id = 0){
		$authoritySiretSQL = new AuthoritySiretSQL($this->getSQLQuery());
		$heliosTransactionSQL = new HeliosTransactionsSQL($this->getSQLQuery());
		$id_list = $heliosTransactionSQL->getAllId($min_id);
		foreach($id_list as $transaction_id){
			$info = $heliosTransactionSQL->getInfo($transaction_id);
			$pes_aller_path = $this->getPesAllerRetriever()->getPath($info['sha1']);
			if (! file_exists($pes_aller_path)){
				echo "Transaction $transaction_id : le fichier PES ALLER n'est pas disponible\n";
				continue;
			}

			$xml = simplexml_load_file($pes_aller_path,"SimpleXMLElement",LIBXML_PARSEHUGE);
			$siret = strval($xml->EnTetePES->IdColl['V']);
			if (! $siret){
				echo "Transaction $transaction_id : le fichier PES ALLER ne contient pas de SIRET !\n";
				continue;
			}
			$authoritySiretSQL->add($info['authority_id'],$siret);
			echo "Transaction $transaction_id : siret $siret ajouté à la collectivite {$info['authority_id']}\n";
		}
	}

	public function getPESRetourListAction(){
        $msg = "";
		try{

            $doc = new DOMDocument();
            $doc->formatOutput = true;
            $doc->preserveWhiteSpace = false;
            $root=$doc->createElement("liste");
            $doc->appendChild($root);

			$module = new Module();
			if (!$module->initByName("helios")) {
				$msg= "Erreur d'initialisation du module";
				throw new Exception('KO');

			}
			$me = new User();

			if (! $me->authenticate()) {
				$msg= "Échec de l'authentification";
				throw new Exception('KO');
			}

            if (!$module->isActive() || !$me->canAccess($module->get("name"))) {
                $msg= "Accès refusé";
                throw new Exception('KO');
            }

			$heliosRetourSQL = new HeliosRetourSQL($this->getSQLQuery());
			$envelops = $heliosRetourSQL->getList($me->get("authority_id"));


			$idCollElement=$doc->createElement("idColl",$me->get('authority_id'));
			$resultatElement=$doc->createElement("resultat");
			$messageElement=$doc->createElement("message");
			$dateDemandeElement=$doc->createElement("dateDemande", date("Y-m-d h:i:s"));
			$root->appendChild($idCollElement);
			$root->appendChild($resultatElement);
			$root->appendChild($messageElement);
			$root->appendChild($dateDemandeElement);


			foreach ($envelops as $envelope) {
				$pes_retourElement=$doc->createElement("pes_retour");
				$pes_retourElement->appendChild($doc->createElement("id",$envelope['id']));
				$pes_retourElement->appendChild($doc->createElement("nom",$envelope["filename"]));
				$pes_retourElement->appendChild($doc->createElement("date",$envelope["date"]));
				$root->appendChild($pes_retourElement);
			}
			$msg="liste réussi";
		}
		catch (Exception $e) {
		   if (! isset($resultatElement)){
                $resultatElement=$doc->createElement("resultat");
                $root->appendChild($resultatElement);
            }
			$resultatElement->appendChild( $doc->createTextNode( "KO" ));
		}
        if (! isset($messageElement)){
            $messageElement=$doc->createElement("message");
            $root->appendChild($messageElement);
        }
		$messageElement->appendChild( $doc->createTextNode( utf8_encode($msg) ));

		header("Content-type: text/xml");
		echo $doc->saveXML();

		$this->controller_exit();
	}


}