<?php

class NounceSQL extends SQL {

	private $passwordGenerator;

	public function __construct(SQLQuery $sqlQuery, PasswordGenerator $passwordGenerator) {
		parent::__construct($sqlQuery);
		$this->passwordGenerator = $passwordGenerator;
	}

	public function create($login,$password){
		$this->menage();
		$nounce = $this->passwordGenerator->getPassword();
		$hash = hash("sha256","$password:$nounce");

		$sql = "INSERT INTO nounce(nounce,login,hash,creation) VALUES (?,?,?,now())";
		$this->query($sql,$nounce,$login,$hash);
		return $nounce;
	}

	public function menage(){
		$sql = "DELETE FROM nounce WHERE creation<?";
		$date = date("c",strtotime("now -5 minutes"));
		$this->query($sql,$date);
	}

	public function verify($login,$nounce,$hash){
		$this->menage();
		$sql = "SELECT id FROM nounce WHERE login=? AND nounce=? AND hash=?";
		$id = $this->queryOne($sql,$login,$nounce,$hash);
		if (! $id){
			return false;
		}
		return true;
	}

}