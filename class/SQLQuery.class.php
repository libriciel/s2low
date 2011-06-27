<?php

class SQLQuery {
	
	const DATABASE_TYPE = "pgsql";
	const DEFAULT_HOST = "localhost";
	
	private $databaseName;
	private $host;
	private $login;
	private $password;
	
	public function __construct($databaseName){
		$this->databaseName = $databaseName;
		$this->setDatabaseHost(self::DEFAULT_HOST);
	}
		
	public function setDatabaseHost($host){
		$this->host = $host;
	}
	
	public function setCredential($login,$password){
		$this->login = $login;
		$this->password = $password;
	}
	
	private function getPdo(){
		static $pdo;
		
		if ( ! $pdo){
			$dsn = self::DATABASE_TYPE . ":host=".$this->host;
			if ($this->databaseName){
				$dsn .= ";dbname=".$this->databaseName;
			}
			$pdo = new PDO($dsn,$this->login,$this->password);
			$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION ); 
			$this->query("SET CLIENT_ENCODING TO LATIN9");
		}
		return $pdo;
	}
	
	private function getStatementFromQuery($query){
		static $tabQuery;
		if (empty($tabQuery[$query])){
			$tabQuery[$query] = $this->getPdo()->prepare($query);
			
		}
		return $tabQuery[$query];
	}
	
	public function query($query,$param = false){
		if ( ! is_array($param)){
			$param = func_get_args();
			array_shift($param);
    	}
    	
    	
    	try {
    		$pdoStatement = $this->getStatementFromQuery($query);
    	} catch (Exception $e) {	
    		throw new Exception($e->getMessage() . " - " .$query);
		}	
		
		try {
			$pdoStatement->execute($param);
		} catch (Exception $e) {
			throw new Exception( $e->getMessage() ." - ". $pdoStatement->queryString . "|" .implode(",",$param));	
		}
		$result = array();
		if ($pdoStatement->columnCount()){
			$result = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
		} 
		return $result;
	}
		
	public function queryOne($query,$param = false){
		if ( ! is_array($param)){
			$param = func_get_args();
			array_shift($param);
    	}
		$result = $this->query($query,$param);
		if (! $result){
			return false;
		}
		
		$result = $result[0];
		if (count($result) == 1){
			return reset($result);
		}
		return $result;
	}
}