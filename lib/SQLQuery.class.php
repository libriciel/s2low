<?php
class SQLQuery {

	const DATABASE_TYPE = "pgsql";
	const DEFAULT_HOST = "localhost";
	const SLOW_QUERY_IN_MS = 2000;

	private $databaseName;
	private $host;
	private $login;
	private $password;
	private $slow_query_in_ms;
	private $pdo;

	public function __construct($databaseName){
		$this->databaseName = $databaseName;
		$this->setDatabaseHost(self::DEFAULT_HOST);
		$this->setSlowQuery(self::SLOW_QUERY_IN_MS);
	}

	public function disconnect(){
		$this->pdo = null;
	}

	public function sleep($time_in_second){
		$this->disconnect();
		sleep($time_in_second);
	}

	public function setDatabaseHost($host){
		$this->host = $host;
	}

	public function setCredential($login,$password){
		$this->login = $login;
		$this->password = $password;
	}

	public function setSlowQuery($millisecond){
		$this->slow_query_in_ms  = $millisecond;
	}

	public function getPdo(){
		if ( ! $this->pdo){
			$dsn = self::DATABASE_TYPE . ":host=".$this->host;
			if ($this->databaseName){
				$dsn .= ";dbname=".$this->databaseName;
			}
			$this->pdo = new PDO($dsn,$this->login,$this->password);
			$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		}
		return $this->pdo;
	}

	public function query($query,$param = false){
		$start = microtime(true);
		if ( ! is_array($param)){
			$param = func_get_args();
			array_shift($param);
		}

		try {
			$pdoStatement = $this->getPdo()->prepare($query);
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

		$duration = microtime(true) - $start;
		if ($duration > $this->slow_query_in_ms ){
			$requete =  $pdoStatement->queryString . "|" .implode(",",$param);
			trigger_error("Requete lente ({$duration}ms): $requete",E_USER_WARNING);
		} // @codeCoverageIgnore

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

	public function queryOneCol($query,$param = false){
		if ( ! is_array($param)){
			$param = func_get_args();
			array_shift($param);
		}
		$result = $this->query($query,$param);
		if (! $result){
			return array();
		}
		$r = array();
		foreach($result as $line){
			$line = array_values($line);
			$r[] = $line[0];
		}
		return $r;
	}
}