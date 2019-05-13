<?php 

class HeliosTransactionsSQL extends SQL {

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

	const MAX_ID = 2147483647; /* (signed) integer max size in PostgreSQL*/

	public function create($filename,$sha1,$user_id,$authority_id,$file_size,$siren){
		$sql = "INSERT INTO helios_transactions(user_id, filename, file_size, siren, sha1, submission_date, authority_id) ".
				" VALUES (?,?,?,?,?,now(),?) RETURNING ID;";
		return $this->queryOne($sql,$user_id,$filename,$file_size,$siren,$sha1,$authority_id);
	}

	public function getInfo($id){
		$sql = "SELECT * FROM helios_transactions WHERE id=?";
		return $this->queryOne($sql,$id);
	}

	public function updateStatus($transaction_id,$status_id,$message){
	    $date = date("Y-m-d H:i:s");
	    $sql = "INSERT INTO helios_transactions_workflow (transaction_id, status_id, date, message) " .
	    		" VALUES( ? , ? , ? , ? ) RETURNING ID ";
  		$id = $this->queryOne($sql,$transaction_id,$status_id,$date,$message);
  		$sql = "UPDATE helios_transactions SET last_status_id=? " .
    			" WHERE id=?";
    	$this->query($sql,$status_id,$transaction_id);
		return $id;
	}

	public function getLastStatusInfo($id){
		$sql = "SELECT * FROM helios_transactions_workflow WHERE transaction_id=? ORDER BY date DESC,id DESC LIMIT 1";
		return $this->queryOne($sql,$id);
	}
	
	public function setSAETransferIdentifier($id,$transfer_identifier){
		$sql = "UPDATE helios_transactions SET sae_transfer_identifier=? WHERE id=?";
		$this->query($sql,$transfer_identifier,$id);
	}
	
	public function getArchiveFromStatusWithSAE($status_id,$date){
		$sql = "SELECT  *,helios_transactions.id as id FROM helios_transactions " .
				" JOIN users ON helios_transactions.user_id = users.id " .
				" JOIN authorities ON users.authority_id=authorities.id " .
				" JOIN helios_transactions_workflow ON helios_transactions_workflow.transaction_id=helios_transactions.id".
				" AND helios_transactions_workflow.status_id=? ".
				" WHERE last_status_id=? AND authorities.pastell_url IS NOT NULL AND authorities.pastell_url != '' ".
				" AND helios_transactions_workflow.date>?";
		return $this->query($sql,$status_id,$status_id,$date);
	}
	
	public function setArchiveURL($transaction_id,$archive_url){
		$sql = "UPDATE helios_transactions SET archive_url=? " .
    			" WHERE id=?";
		$this->query($sql,$archive_url,$transaction_id);
	}
	
	public function getTransactionToDelete(){
		$sql = "SELECT * FROM helios_transactions WHERE last_status_id=10 OR last_status_id=6";
		return $this->query($sql);
	}
	
	public function updateLastStatusId(){
		$sql2 = "UPDATE helios_transactions SET last_status_id = ? WHERE id=?";
		$sql = "SELECT id FROM helios_transactions WHERE last_status_id IS NULL";

		$id_list = $this->queryOneCol($sql);
		foreach($id_list as $transaction_id) {
			$last_status_id = $this->getLatestStatusId($transaction_id);
			$this->query($sql2, $last_status_id, $transaction_id);
			echo "$transaction_id : $last_status_id\n";
		}
	}

	public function getLatestStatusId($id){
		$sql = "SELECT status_id " .
				" FROM helios_transactions_workflow " .
				" WHERE transaction_id=? " .
				" ORDER BY date DESC LIMIT 1";
		return $this->queryOne($sql,$id);
	}

	public function setLastStatusId($transaction_id){
		$last_status_id = $this->getLatestStatusId($transaction_id);
		$sql = "UPDATE helios_transactions SET last_status_id = ? WHERE id=?";
		$this->query($sql,$last_status_id,$transaction_id);
	}
	
	public function getIdsByStatus($status_id,$authority_id = 0){
		$sql = "SELECT  id FROM helios_transactions " .
				" WHERE last_status_id=? ";
		$data = [$status_id];
		if ($authority_id){
			$sql.=" AND authority_id= ? ";
			$data[] = $authority_id;
		}

		$sql .= " ORDER BY id";
		return $this->queryOneCol($sql,$data);
	}
	
	public function getIdByNomFicAndCodCol($nomFic, $cod_col)  {
		$sql = "SELECT id FROM helios_transactions WHERE xml_nomfic = ? AND xml_cod_col=?";
		return $this->queryOneCol($sql,$nomFic,$cod_col);
	}

    public function getIdByNomFic($nomFic) {
        $sql = "SELECT id FROM helios_transactions WHERE xml_nomfic = ? ";
        return $this->queryOneCol($sql,$nomFic);
    }


	public function nomFicExists($nom_fic,$cod_col){
		$sql = "SELECT count(*) FROM helios_transactions WHERE xml_nomfic= ? AND xml_cod_col=?";
		return $this->queryOne($sql, $nom_fic,$cod_col);
	}
	
	public function setNomFic($transaction_id,$nom_fic){
		$sql = "UPDATE helios_transactions SET xml_nomfic=? WHERE id=?";
		$this->query($sql,$nom_fic,$transaction_id);
	}

	public function setInfoFromPESAller($transaction_id, array $info){
		$sql = "UPDATE helios_transactions SET xml_nomfic=?, xml_cod_col=?, xml_cod_bud=?, xml_id_post=? WHERE id=?";
		$this->query($sql,$info['nom_fic'],$info['cod_col'],$info['cod_bud'],$info['id_post'],$transaction_id);
	}
	
	public function setCompleteName($transaction_id,$completeName){
		$sql = "UPDATE helios_transactions SET complete_name = ? WHERE id= ?";
		$this->query($sql,$completeName,$transaction_id);
	}
	
	public function mustSendWarning($transaction_id)  {
		$sql = 	"SELECT submission_date, user_id " .
					" FROM helios_transactions " .
					" WHERE id = ? " .
					" AND warning_sent IS NULL" .
					" AND date_part('epoch', now()) - date_part('epoch', submission_date) > ? ";
		return $this->queryOne($sql,$transaction_id,self::SEND_WARNING_AFTER_SECOND);
	}
	
	public function setSendWarning($transaction_id) {
		$sql = "UPDATE helios_transactions SET warning_sent = 1 WHERE id = ?";
		$this->query($sql,$transaction_id);
	}
	
	public function delete($id){
		$sql  = "DELETE FROM helios_transactions_workflow WHERE transaction_id=?";
		$this->query($sql,$id);
		$sql = "DELETE FROM helios_transactions WHERE id=?";
		$this->query($sql,$id);
	}
	
	public function setAcquitFilename($id,$acquit_filename) {
		$sql = "UPDATE helios_transactions SET acquit_filename =  ? WHERE id = ?";
		$this->query($sql,$acquit_filename,$id);
	}

	public function getAll(){
		$sql = "SELECT * FROM helios_transactions ORDER BY id";
		return $this->query($sql);
	}

	public function getWorkflow($transaction_id){
		$sql = "SELECT * FROM helios_transactions_workflow WHERE transaction_id=? ORDER BY date";
		return $this->query($sql,$transaction_id);
	}

	public function isDuplicate($sha1) {
		$sql = "select count(id) FROM helios_transactions WHERE sha1=?";
		return $this->queryOne($sql,$sha1);
	}

	public function getAllId($min_id = 0){
		$sql = "SELECT id FROM helios_transactions WHERE id > ? ORDER BY id";
		return $this->queryOneCol($sql,$min_id);
	}

	public function setSignatureTechnique($transaction_id,$new_sha1,$new_file, $signature_technique=true){
		$sql = "UPDATE helios_transactions SET sha1=?, file_size=?,signature_technique=? WHERE id=?";
		$this->query($sql,$new_sha1,$new_file,$signature_technique,$transaction_id);
	}

	public function getNbByStatus($status_id){
		$sql = "SELECT count(*) FROM helios_transactions WHERE last_status_id=?";
		return $this->queryOne($sql,$status_id);
	}

	public function getNbByStatusAndDate($status_id,$submission_date_max){
		$sql = "SELECT count(*) FROM helios_transactions WHERE last_status_id=? AND submission_date<?";
		return $this->queryOne($sql,$status_id,$submission_date_max);
	}

	public function getNonAcquitte(){
		$sql = "SELECT helios_transactions.id, helios_transactions.filename, xml_nomfic,helios_transactions.submission_date,helios_ftp_dest,xml_id_post,xml_cod_bud FROM helios_transactions " .
			" JOIN authorities ON authorities.id=helios_transactions.authority_id " .
			" WHERE last_status_id=3 AND helios_transactions.submission_date < ? " .
			" ORDER BY submission_date DESC ";

		$today = date("Y-m-d");
		return $this->query($sql,$today);
	}
	
	public function getNbTransactionByMonth(){
		$sql = "SELECT count(*) as nb,date_trunc('month', submission_date) as month  FROM helios_transactions " .
			" WHERE submission_date >= '2015-01-01'".
			" GROUP BY month" .
			" ORDER BY month DESC";
		return $this->query($sql);
	}

	public function getNextTransactionToSendInCloud(){
	    $sql = "SELECT id,sha1,filename FROM helios_transactions WHERE is_in_cloud=FALSE ORDER BY id ASC LIMIT 1";
	    return $this->queryOne($sql);
    }

    public function setTransactionInCloud($id){
	    $sql = "UPDATE helios_transactions SET is_in_cloud=TRUE WHERE id=?";
	    $this->query($sql,$id);
    }

    public function setTransactionInCloudRemove($id){
        $sql = "UPDATE helios_transactions SET is_in_cloud=FALSE WHERE id=?";
        $this->query($sql,$id);
    }

    public function getAllTransactionToSendInCloud(){
        $sql = "SELECT id,sha1,filename FROM helios_transactions WHERE is_in_cloud=FALSE ORDER BY id ASC";
        return $this->query($sql);
    }

    public function getAllForExport($authority_id,$min_transaction_id,$max_trasaction_id){
		$sql = "SELECT id,sha1,filename,acquit_filename FROM helios_transactions WHERE authority_id=? AND id >= ? AND id<=? ORDER BY id";
		return $this->query($sql,$authority_id,$min_transaction_id,$max_trasaction_id);
	}

}