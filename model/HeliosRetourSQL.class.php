<?php

class HeliosRetourSQL extends SQL {

	const STATUS_NON_LU = 0;
	const STATUS_LU = 1;
	
	public function add($authority_id,$siret,$filename){
		$siren = substr($siret, 0,9);
		$sql = "INSERT INTO helios_retour(authority_id, siren, filename, status, date,siret) " .
				" VALUES ( ?,?,?,0,now(),?) returning id";
		return $this->queryOne($sql,$authority_id,$siren,$filename,$siret);
	}
	
	public function getInfo($helios_retour_id){
		$sql = "SELECT * FROM helios_retour WHERE id=?";
		return $this->queryOne($sql,$helios_retour_id);
	}

	public function getInfoFromFilename($authority_id,$filename){
		$sql = "SELECT * FROM helios_retour ".
				" WHERE authority_id = ? AND filename = ?";
		return $this->queryOne($sql,$authority_id,$filename);
	}

	public function getList($authority_id){
		$sql = "SELECT * FROM helios_retour WHERE status = ? AND authority_id = ? ";
		return $this->query($sql,self::STATUS_NON_LU,$authority_id);
	}

	public function changeStatus($id, $status){
		$sql = "UPDATE helios_retour SET status = ? WHERE id = ?";
		$this->query($sql,$status, $id);
	}

    public function getAllIdPESRetourToSendInCloud(){
        $sql = "SELECT id FROM helios_retour WHERE is_in_cloud=FALSE ORDER BY id";
        return $this->queryOneCol($sql);
    }

    public function getFilename($helios_retour_id){
        $sql = "SELECT filename FROM helios_retour WHERE id=?";
        return $this->queryOne($sql,$helios_retour_id);
    }

    public function setPesRetourNotAvailable(int $helios_retour_id){
        $sql = "UPDATE helios_retour SET not_available=? WHERE id=?";
        $this->query($sql,true,$helios_retour_id);
    }

    public function setPesRetourInCloud(int $helios_retour_id): void
    {
        $sql = "UPDATE helios_retour SET is_in_cloud=TRUE WHERE id=?";
        $this->query($sql,$helios_retour_id);
    }

    public function getByFilename(string $filename)
    {
        $sql = "SELECT id FROM helios_retour WHERE filename=?";
        return $this->queryOne($sql,$filename);
    }

    public function isAvailable(int $object_id): bool
    {
        $sql = "SELECT not_available FROM helios_retour WHERE id=?";
        return ! $this->queryOne($sql,$object_id);
    }

    public function setAvailable(int $object_id, bool $available)
    {
        $sql = "UPDATE helios_retour SET not_available=? WHERE id=?";
        $this->query($sql, intval(! $available),$object_id);
    }
}