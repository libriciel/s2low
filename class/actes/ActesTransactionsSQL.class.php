<?php 
class ActesTransactionsSQL extends SQL{


	public function getInfo($id){
		$sql = "SELECT * FROM actes_transactions WHERE id=?";
		return $this->queryOne($sql,$id);
	}
	
	public function getStatusInfo($id,$status){
		$sql = "SELECT * FROM actes_transactions_workflow WHERE transaction_id=? AND status_id=?";
		return $this->queryOne($sql,$id,$status);
	}

	public function getLastStatusInfo($id){
		$sql = "SELECT * FROM actes_transactions_workflow WHERE transaction_id=? ORDER BY date DESC LIMIT 1";
		return $this->queryOne($sql,$id);
	}

	
	public function getAllFile($id){
		$sql = "SELECT * FROM actes_included_files WHERE transaction_id=? ORDER BY id";
		return $this->query($sql,$id);
	}
	
	public function setArchiveURL($transaction_id,$archive_url){
		$sql = "UPDATE actes_transactions SET archive_url=? " .
    			" WHERE id=?";
		$this->query($sql,$archive_url,$transaction_id);
	}


	public function updateStatus($transaction_id,$status_id,$message,$flux_retour=''){

		$message = substr($message,0,512);

	    $date = date("Y-m-d H:i:s");
	    $sql = "INSERT INTO actes_transactions_workflow (transaction_id, status_id, date, message,flux_retour) " .
	    		" VALUES( ? , ? , ? , ? ,?) RETURNING ID";

  		$id = $this->queryOne($sql,$transaction_id,$status_id,$date,$message,$flux_retour);


    	$sql = "UPDATE actes_transactions SET last_status_id=? " .
    			" WHERE id=?";

    	$this->query($sql,$status_id,$transaction_id);
		return $id;
	}
	
	public function getArchiveFStatus($status_id,$authority_id=0){
		$sql = "SELECT  actes_transactions.id as id FROM actes_transactions " .
				" WHERE last_status_id=? " ;
		$data = [$status_id];
		if ($authority_id){
			$sql.=" AND authority_id= ? ";
			$data[] = $authority_id;
		}
		$sql .= "ORDER BY id";
		return $this->query($sql,$data);
	}

	public function getArchiveFromStatusWithSAE($status_id){
        $sql = "SELECT  *,actes_transactions.id as id FROM actes_transactions " .
            " JOIN authorities ON actes_transactions.authority_id=authorities.id " .
            " WHERE last_status_id=? AND authorities.pastell_url IS NOT NULL AND authorities.pastell_url != '' ";
        return $this->query($sql,$status_id);
    }

    public function getEnveloppeIdByTransactionsStatus($last_status_id,$antivirus_check = true){
        $sql = "SELECT DISTINCT envelope_id FROM actes_transactions WHERE last_status_id=? AND antivirus_check=?";
        return $this->queryOneCol($sql,$last_status_id,$antivirus_check);
    }

    public function getIdByEnvelopeId($envelope_id){
        $sql = "SELECT id FROM actes_transactions WHERE envelope_id=?";
        return $this->queryOneCol($sql,$envelope_id);

    }

	public function getTransactionIdFromStatus($status_id, $antivirus_check = true){
		$sql = "SELECT  actes_transactions.id as id FROM actes_transactions " .
				" WHERE last_status_id=? AND antivirus_check=?";
		return $this->queryOneCol($sql,$status_id,$antivirus_check);
	}

	public function getTransactionIdByStatus($status_id){
		$sql = "SELECT  actes_transactions.id as id FROM actes_transactions " .
			" WHERE last_status_id=?";
		return $this->queryOneCol($sql,$status_id);
	}


    public function getLastArchiveFromStatus($status_id,$start_date){
        $sql = "SELECT  actes_transactions.*,authorities.*,actes_transactions.id as id FROM actes_transactions " .
            " JOIN authorities ON actes_transactions.authority_id=authorities.id " .
            " JOIN actes_transactions_workflow ON actes_transactions_workflow.transaction_id=actes_transactions.id".
            " AND actes_transactions_workflow.status_id=? ".
            " WHERE last_status_id=? AND authorities.pastell_url IS NOT NULL " .
            " AND authorities.pastell_url != '' AND actes_transactions_workflow.date > ?";
        return $this->query($sql,$status_id,$status_id,$start_date);
    }

    public function getByStatusSinceDate($status_id,$date_status){
		$sql = "SELECT DISTINCT actes_transactions.id FROM actes_transactions " .
				" JOIN actes_transactions_workflow ON actes_transactions.id = actes_transactions_workflow.transaction_id AND actes_transactions.last_status_id=actes_transactions_workflow.status_id " .
				" WHERE last_status_id=? AND date<?";
		return $this->queryOneCol($sql,$status_id,$date_status);
	}

	
	public function getEnvelopeToDelete(){
		$sql = "SELECT  actes_envelopes.*,actes_transactions.id as transaction_id, actes_transactions.user_id " .
				" FROM actes_transactions " .
				" JOIN actes_envelopes ON actes_transactions.envelope_id=actes_envelopes.id " .
				" WHERE last_status_id=13 OR last_status_id = 6";
		return $this->query($sql);
	}
	
		
	public function getNextNumeroTransfert(){
		$sql = "SELECT count(*) + 1 FROM actes_transactions_workflow WHERE status_id=? AND date(date) = date(now());";
		return $this->queryOne($sql,12);
	}
	
	public function getLatestDate($id){
		$all_id = array($id);
		$relatedTransaction = $this->getRelatedTransaction($id);
		foreach($relatedTransaction as $transaction){
			$all_id[] = $transaction['id'];
		}
		$sql = "SELECT max(date) " .
				" FROM actes_transactions_workflow " .
				" WHERE status_id IN (4,11,7) " .
				" AND transaction_id IN (".implode(",",$all_id).")";
		return $this->queryOne($sql);
	}
	
	public function getRelatedTransaction($id){
		$result = array();
		$sql = "SELECT * ".
  				" FROM actes_transactions ".
  				" WHERE related_transaction_id=?";
		foreach($this->query($sql,$id) as $line){
			$result[] = $line;
			$result = array_merge($result,$this->getRelatedTransaction($line['id']));
		}
		return $result;
	}
	
	public function setSAETransferIdentifier($id,$transfer_identifier){
		$sql = "UPDATE actes_transactions SET sae_transfer_identifier=? WHERE id=?";
		$this->query($sql,$transfer_identifier,$id);
	}
	
	public function getDateTampon($id){
		$sql = "SELECT actes_envelopes.submission_date, actes_transactions_workflow.date, actes_transactions.unique_id FROM actes_transactions, actes_envelopes, actes_transactions_workflow " .
				" WHERE actes_transactions.envelope_id = actes_envelopes.id " .
				" AND actes_transactions.id = ? ".
				" AND actes_transactions_workflow.transaction_id = actes_transactions.id " .
				" AND actes_transactions_workflow.status_id=?";
		return $this->queryOne($sql,$id,4);
	}
	
	public function getTransactionForAntiVirus(){
		$sql = "SELECT DISTINCT id FROM actes_transactions WHERE last_status_id=? AND antivirus_check=?";
		return $this->queryOneCol($sql,1,0);
	}
	
	public function setAntivirusCheck($transaction_id){
		$sql = "UPDATE actes_transactions SET antivirus_check=? WHERE id=?";
		$this->query($sql,1,$transaction_id);
	}
	
	public function updateLastStatusId(){
		$sql = "SELECT id FROM actes_transactions";
	
		$id_list = $this->queryOneCol($sql);
		foreach($id_list as $transaction_id) {
			//echo "$transaction_id : ";
			$status=$this->getlaststatusforid($transaction_id);
			//echo "$status\n";
			$sqlupdate = "update actes_transactions set last_status_id = ? where  id = ?";
			$this->query($sql,$status,$transaction_id);
        }
    }
	
	public function getlaststatusforid($idtrans){
		$sql = " select status_id from actes_transactions_workflow where transaction_id = ? ORDER BY id DESC limit 1";
		$status = $this->queryOneCol($sql,$idtrans);
        return $status[0];
    }

    public function getLastTransactionWorkflowInfo($id){
        $sql = " select * from actes_transactions_workflow where transaction_id = ? ORDER BY id DESC limit 1";
        return $this->queryOne($sql,$id);
    }

    public function getNbTransactionByMonth(){
    	$sql = "SELECT count(*) as nb,date_trunc('month', submission_date) as month  FROM actes_transactions " .
			" JOIN actes_envelopes ON actes_transactions.envelope_id = actes_envelopes.id" .
			" WHERE actes_transactions.type = '1' AND submission_date>'2015-01-01'" .
			" GROUP BY month" .
			" ORDER BY month DESC";
		return $this->query($sql);
	}

	public function getBySirenAndNumeroInterne($siren,$numero_interne,$type = '1',$type_reponse_not_null = false){
        $sql = "SELECT actes_transactions.id from actes_transactions " .
            " JOIN actes_envelopes ON actes_transactions.envelope_id = actes_envelopes.id " .
            " WHERE siren=? AND number=? AND type=? " ;

        if ($type_reponse_not_null){
            $sql.= " AND type_reponse IS NOT NULL ";
        }

        $sql .= " ORDER BY submission_date DESC";


        return $this->queryOne($sql,$siren,$numero_interne,$type);
    }

    public function getNbByStatus($status_id){
        $sql = "SELECT count(id) FROM actes_transactions WHERE last_status_id=?";
        return $this->queryOne($sql,$status_id);
    }

    public function getLastDemandeClassificationTransmis($siren){
        $sql = "SELECT actes_transactions.id FROM actes_transactions ".
            " JOIN actes_envelopes ON actes_transactions.envelope_id = actes_envelopes.id " .
            " WHERE siren=? AND type='7' AND last_status_id=? ORDER BY submission_date DESC";
        return $this->queryOne($sql,$siren,ActesStatusSQL::STATUS_TRANSMIS);
    }

    public function setUniqueID($acteID,$unique_id) {
        $sql = "UPDATE actes_transactions SET unique_id=? WHERE id=?";
        $this->query($sql,$unique_id,$acteID);
    }

    public function createRelatedTransaction($envelope_id, $type, $decision_date, $related_transaction_id)  {
        $sql = "INSERT INTO actes_transactions (envelope_id,type,related_transaction_id," .
            " nature_code,nature_descr,title, subject, number,classification,classification_date,decision_date," .
            " unique_id, archive_url,broadcast_emails,broadcast_send_sources, broadcasted,user_id,authority_id) " .
            " SELECT ?,?,?,nature_code,nature_descr,title, subject, number,classification" .
            ",classification_date,?,unique_id, archive_url,broadcast_emails,broadcast_send_sources, broadcasted,user_id,authority_id " .
            " FROM actes_transactions where id = ? RETURNING id";
        return $this->queryOne($sql,$envelope_id,$type,$related_transaction_id,$decision_date,$related_transaction_id);
    }



    public function create($envelope_id, $status,$user_id,$authority_id) {
        $sql = "INSERT INTO actes_transactions(envelope_id,last_status_id,user_id,authority_id) VALUES (?,?,?,?) RETURNING ID;";
        return $this->queryOne($sql, $envelope_id, $status, $user_id,$authority_id);
    }

    public function guessUniqueId($transaction_id){
        //034-000000000-20170701-20170728C-AI

        $sql = "SELECT * FROM actes_transactions ".
            " JOIN actes_envelopes ON actes_transactions.envelope_id = actes_envelopes.id " .
            " WHERE actes_transactions.id=?";
        $info = $this->queryOne($sql,$transaction_id);

        $sql2 = "SELECT short_descr FROM actes_natures WHERE id=?";
        $nature = $this->queryOne($sql2,$info['nature_code']);


        return sprintf(
            "%s-%s-%s-%s-%s",
            $info['department'],
            $info['siren'],
            date("Ymd",strtotime($info['decision_date'])),
            $info['number'],
            $nature
        );
    }

    public function getNbByStatusAndDate($status_id,$submission_date_max){
        $sql = "SELECT count(*) FROM actes_transactions " .
            " JOIN actes_envelopes ON actes_transactions.envelope_id = actes_envelopes.id " .
            " WHERE last_status_id=? AND submission_date <?";
        return $this->queryOne($sql,$status_id,$submission_date_max);
    }

    public function getTransactionToAutoBroadcast(){
        $sql = "SELECT actes_transactions.id  FROM actes_transactions ".
            " WHERE last_status_id IN ('-1','4','7','8','11','21')  AND auto_broadcasted=false AND type IN ('1','2','3','4','5','6')";
        return $this->queryOneCol($sql);
    }

    public function setBroadcasted($transactionId) {
        $sql = "UPDATE actes_transactions SET broadcasted = TRUE " .
            " WHERE actes_transactions.id = ?";
        $this->query($sql, $transactionId);
    }

    public function setAutoBroadcasted($transactionId){
        $sql = "UPDATE actes_transactions SET auto_broadcasted = TRUE " .
            " WHERE actes_transactions.id = ?" ;
        $this->query($sql,$transactionId);
    }

    public function getNbByStatusAndAuthority($status_id,$authority_id){
        $sql = "SELECT count(*) FROM actes_transactions " .
            " WHERE last_status_id=? AND authority_id = ?";
        return $this->queryOne($sql,$status_id,$authority_id);
    }

    public function getListByStatusAndAuthority($status_id,$authority_id,$offset,$limit){
        $offset = intval($offset);
        $limit = intval($limit);
        $sql = "SELECT id,subject,number,date(decision_date),nature_descr,classification,type FROM actes_transactions " .
            " WHERE last_status_id=? AND authority_id = ? ORDER BY actes_transactions.id DESC OFFSET $offset LIMIT $limit";
        return $this->query($sql,$status_id,$authority_id);
    }

    public function listDocumentPrefectureNonLu($authority_id){
		$sql = "SELECT id,type,related_transaction_id,number,unique_id,last_status_id FROM actes_transactions " .
			" WHERE authority_id=? AND type IN ('2','3','4','5') AND type_reponse IS NULL AND lu=false" .
			" ORDER BY id";
		return $this->query($sql,$authority_id);
	}

	public function markAsRead($authority_id,$transaction_id){
		$sql = "UPDATE actes_transactions SET lu=true WHERE id=? AND authority_id=?";
		$this->query($sql,$transaction_id,$authority_id);
	}

}