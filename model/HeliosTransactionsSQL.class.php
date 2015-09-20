<?php 

class HeliosTransactionsSQL {

	/**
	 * @var SQLQuery
	 */
	private $sqlQuery;
	
	const ERREUR = -1;
	const ANNULE = 0;
	const POSTE = 1;
	const ATTENTE = 2;
	const TRANSMIS = 3;
	const ACQUITTER = 4;
	const VALIDER = 5; //non utilisé
	const REFUSER = 6;
	const EN_TRAITEMENT = 7;
	const INFORMATION_DISPONIBLE = 8;
	const ACCEPTE_SAE = 10;
	const REFUSER_SAE = 11;
	const ATTENTE_POSTEE = 14;
	const ATTENTE_SIGNEE = 13;
	
	const SEND_WARNING_AFTER_SECOND = 172800;
	
	public function __construct(SQLQuery $sqlQuery){
		$this->sqlQuery = $sqlQuery;
	}

	public function create($filename,$sha1,$user_id,$authority_id,$file_size,$siren){
		$sql = "INSERT INTO helios_transactions(user_id, filename, file_size, siren, sha1, submission_date, authority_id) ".
				" VALUES (?,?,?,?,?,now(),?) RETURNING ID;";
		return $this->sqlQuery->queryOne($sql,$user_id,$filename,$file_size,$siren,$sha1,$authority_id);
	}

	public function getInfo($id){
		$sql = "SELECT * FROM helios_transactions WHERE id=?";
		return $this->sqlQuery->queryOne($sql,$id);
	}
	
	public function getLatestStatusId($id){
		$sql = "SELECT status_id " .
				" FROM helios_transactions_workflow " .
				" WHERE transaction_id=? " .
				" ORDER BY date DESC LIMIT 1";
		return $this->sqlQuery->queryOne($sql,$id);
	}
	
	public function updateStatus($transaction_id,$status_id,$message){
	    $date = date("Y-m-d H:i:s");
	    $sql = "INSERT INTO helios_transactions_workflow (transaction_id, status_id, date, message) " .
	    		" VALUES( ? , ? , ? , ? )";
  		$this->sqlQuery->query($sql,$transaction_id,$status_id,$date,$message);
  		$sql = "UPDATE helios_transactions SET last_status_id=? " .
    			" WHERE id=?";
    	$this->sqlQuery->query($sql,$status_id,$transaction_id);
	}
	
	public function setSAETransferIdentifier($id,$transfer_identifier){
		$sql = "UPDATE helios_transactions SET sae_transfer_identifier=? WHERE id=?";
		$this->sqlQuery->query($sql,$transfer_identifier,$id);
	}
	
	public function getArchiveFromStatusWithSAE($status_id){
		$sql = "SELECT  *,helios_transactions.id as id FROM helios_transactions " .
				" JOIN users ON helios_transactions.user_id = users.id " .
				" JOIN authorities ON users.authority_id=authorities.id " .
				" WHERE last_status_id=? AND authorities.pastell_url IS NOT NULL AND authorities.pastell_url != '' ";
		return $this->sqlQuery->query($sql,$status_id);	
	}
	
	public function setArchiveURL($transaction_id,$archive_url){
		$sql = "UPDATE helios_transactions SET archive_url=? " .
    			" WHERE id=?";
		$this->sqlQuery->query($sql,$archive_url,$transaction_id);
	}
	
	public function getTransactionToDelete(){
		$sql = "SELECT * FROM helios_transactions WHERE last_status_id=10 OR last_status_id=11";
		return $this->sqlQuery->query($sql);
	}

	public function updateLastStatusId(){
		$sql2 = "UPDATE helios_transactions SET last_status_id = ? WHERE id=?";
		$sql = "SELECT id FROM helios_transactions WHERE last_status_id IS NULL";

		$id_list = $this->sqlQuery->queryOneCol($sql);
		foreach($id_list as $transaction_id) {
			$last_status_id = $this->getLatestStatusId($transaction_id);
			$this->sqlQuery->query($sql2, $last_status_id, $transaction_id);
			echo "$transaction_id : $last_status_id\n";
		}
	}

	public function setLastStatusId($transaction_id){
		$last_status_id = $this->getLatestStatusId($transaction_id);
		$sql = "UPDATE helios_transactions SET last_status_id = ? WHERE id=?";
		$this->sqlQuery->query($sql,$last_status_id,$transaction_id);
	}
	
	public function getIdsByStatus($status_id){
		$sql = "SELECT  id FROM helios_transactions " .
				" WHERE last_status_id=?";
		return $this->sqlQuery->queryOneCol($sql,$status_id);
	}
	
	public function getIdByNomFic($nomFic)  {
		$sql = "SELECT id FROM helios_transactions WHERE xml_nomfic = ?";
		return $this->sqlQuery->queryOne($sql,$nomFic);
	}
	
	public function nomFicExists($nom_fic){
		$sql = "SELECT count(*) FROM helios_transactions WHERE xml_nomfic= ?";
		return $this->sqlQuery->queryOne($sql,$nom_fic);
	}
	
	public function setNomFic($transaction_id,$nom_fic){
		$sql = "UPDATE helios_transactions SET xml_nomfic=? WHERE id=?";
		$this->sqlQuery->query($sql,$nom_fic,$transaction_id);
	}
	
	public function setCompleteName($transaction_id,$completeName){
		$sql = "UPDATE helios_transactions SET complete_name = ? WHERE id= ?";
		$this->sqlQuery->query($sql,$completeName,$transaction_id);
	}
	
	public function mustSendWarning($transaction_id)  {
		$sql = 	"SELECT submission_date, user_id " .
					" FROM helios_transactions " .
					" WHERE id = ? " .
					" AND warning_sent IS NULL" .
					" AND date_part('epoch', now()) - date_part('epoch', submission_date) > ? ";
		return $this->sqlQuery->queryOne($sql,$transaction_id,self::SEND_WARNING_AFTER_SECOND);
	}
	
	public function setSendWarning($transaction_id) {
		$sql = "UPDATE helios_transactions SET warning_sent = 1 WHERE id = ?";
		$this->sqlQuery->query($sql,$transaction_id);
	}
	
	public function delete($id){
		$sql  = "DELETE FROM helios_transactions_workflow WHERE transaction_id=?";
		$this->sqlQuery->query($sql,$id);
		$sql = "DELETE FROM helios_transactions WHERE id=?";
		$this->sqlQuery->query($sql,$id);
	}
	
	public function setAcquitFilename($id,$acquit_filename) {
		$sql = "UPDATE helios_transactions SET acquit_filename =  ? WHERE id = ?";
		$this->sqlQuery->query($sql,$acquit_filename,$id);
	}

	public function getAll(){
		$sql = "SELECT * FROM helios_transactions ORDER BY id";
		return $this->sqlQuery->query($sql);
	}

	public function getWorkflow($transaction_id){
		$sql = "SELECT * FROM helios_transactions_workflow WHERE transaction_id=? ORDER BY date";
		return $this->sqlQuery->query($sql,$transaction_id);
	}

	public function isDuplicate($sha1) {
		$sql = "select count(id) FROM helios_transactions WHERE sha1=?";
		return $this->sqlQuery->queryOne($sql,$sha1);
	}



}