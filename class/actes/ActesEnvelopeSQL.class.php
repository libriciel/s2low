<?php 


class ActesEnvelopeSQL extends SQL
{

    public function getInfo($id)
    {
        $sql = "SELECT * FROM actes_envelopes WHERE id=?";
        return $this->queryOne($sql, $id);
    }

    public function findByAnomalieEnveloppeName($anomalie_enveloppe_name)
    {
        $file_path = "%" . substr($anomalie_enveloppe_name, 4, -3) . "%";
        $sql = "SELECT id FROM actes_envelopes  WHERE file_path LIKE ? ";
        return $this->queryOne($sql, $file_path);
    }

    public function create($user_id, $file_path,$siren = '000000000')
    {
        $sql = "INSERT INTO actes_envelopes(user_id,file_path,submission_date,siren) VALUES(?,?,now(),?) RETURNING ID";
        return $this->getSQLQuery()->queryOne($sql, $user_id, $file_path,$siren);
    }

    public function createRelatedEnveloppe($envelope_id,$file_path, $file_size)  {
        $sql = "INSERT INTO actes_envelopes(user_id,submission_date,siren,department,district," .
                "authority_type_code,name,telephone,email,file_path,file_size,return_mail)" .
                " SELECT user_id,now(), siren,department,district,authority_type_code,name,telephone," .
                " email,?,?,return_mail FROM actes_envelopes WHERE id= ? RETURNING id";
        return $this->queryOne($sql,$file_path,$file_size,$envelope_id);
    }

    public function getLastEnvelope(){
        $sql = "SELECT * FROM actes_envelopes ORDER BY id DESC LIMIT 1";
        return $this->queryOne($sql);
    }

    public function listEnveloppe($date_begin,$date_end,$authority_id = false,$authority_group_id = false){
        $sql = "SELECT actes_envelopes.* FROM actes_envelopes " .
                " JOIN users ON actes_envelopes.user_id = users.id ".
                " JOIN authorities ON users.authority_id = authorities.id ".
                " WHERE submission_date>=? AND submission_date<=? ";
        $data = array($date_begin,$date_end);
        if ($authority_id){
            $sql.=" AND users.authority_id = ? ";
            $data[] = $authority_id;
        }
        if ($authority_group_id){
            $sql.=" AND authorities.authority_group_id = ? ";
            $data[] = $authority_group_id;
        }

        return $this->query($sql,$data);
    }


	public function getNextTransactionToSendInCloud(){
		$sql = "SELECT id,file_path FROM actes_envelopes WHERE is_in_cloud=FALSE ORDER BY id ASC LIMIT 1";
		return $this->queryOne($sql);
	}

	public function setTransactionInCloud($id){
		$sql = "UPDATE actes_envelopes SET is_in_cloud=TRUE WHERE id=?";
		$this->query($sql,$id);
	}

	public function setTransactionInCloudRemove($id){
		$sql = "UPDATE actes_envelopes SET is_in_cloud=FALSE WHERE id=?";
		$this->query($sql,$id);
	}

	public function getAllTransactionToSendInCloudHandle(){
		$sql = "SELECT id,file_path FROM actes_envelopes WHERE is_in_cloud=FALSE ORDER BY id ASC";
		$this->getSQLQuery()->prepareAndExecute($sql);
		return $this->getSQLQuery();
	}

	public function getAllEnvelopepIdToSendInCloud(){
		$sql = "SELECT id FROM actes_envelopes WHERE is_in_cloud=FALSE ORDER BY id ASC";
		return $this->queryOneCol($sql);
	}

	public function getOlderTransactionHandle($min_date,$max_date){
		$sql = "SELECT id,file_path,submission_date FROM actes_envelopes " .
				" WHERE is_in_cloud=TRUE AND submission_date > ? AND submission_date<?  ORDER BY id ASC";
		$this->getSQLQuery()->prepareAndExecute($sql,$min_date,$max_date);
		return $this->getSQLQuery();
	}


}