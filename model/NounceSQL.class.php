<?php

class NounceSQL extends SQL {

	private $passwordGenerator;

	public function __construct(SQLQuery $sqlQuery, PasswordGenerator $passwordGenerator) {
		parent::__construct($sqlQuery);
		$this->passwordGenerator = $passwordGenerator;
	}

	public function create($login,$password,$authority_id){
		$this->menage();
		$nounce = $this->passwordGenerator->getPassword();
		$hash = hash("sha256","$password:$nounce");

		$sql = "INSERT INTO nounce(nounce,login,hash,creation,authority_id) VALUES (?,?,?,now(),?)";
		$this->query($sql,$nounce,$login,$hash,$authority_id);
		return $nounce;
	}

	public function menage(){
		$sql = "DELETE FROM nounce WHERE creation<?";
		$date = date("c",strtotime("now -5 minutes"));
		$this->query($sql,$date);
	}

	public function verify($login,$nounce,$hash){
		$this->menage();
		$sql = "SELECT authority_id FROM nounce WHERE login=? AND nounce=? AND hash=?";
		$authority_id = $this->queryOne($sql,$login,$nounce,$hash);
		if (! $authority_id){
			return false;
		}
		return $authority_id;
	}

}