<?php


abstract class S2lowTestCase extends PHPUnit_Extensions_Database_TestCase {
	
	private $sqlQuery;
	
	public function __construct($name = NULL, array $data = array(), $dataName = ''){
		parent::__construct($name,$data,$dataName);
		
		$sqlQuery = new SQLQuery(DB_DATABASE_TEST);
		$sqlQuery->setCredential(DB_USER_TEST,DB_PASSWORD_TEST);
		$sqlQuery->setDatabaseHost(DB_HOST_TEST);
		
		$this->databaseConnection = $this->createDefaultDBConnection($sqlQuery->getPdo(), DB_DATABASE_TEST);
		
		$this->sqlQuery = $sqlQuery;
		
	}
	
	
	/**
	 * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
	 */
	public function getConnection() {
		return $this->databaseConnection;
	}
	
	/**
	 * @return PHPUnit_Extensions_Database_DataSet_IDataSet
	 */
	public function getDataSet() {
		return new PHPUnit_Extensions_Database_DataSet_YamlDataSet( __DIR__."/database_data.yml");
	}
	
 	protected function getSetUpOperation() {
        return new \PHPUnit_Extensions_Database_Operation_Composite(array(
            \PHPUnit_Extensions_Database_Operation_Factory::DELETE_ALL(),
            \PHPUnit_Extensions_Database_Operation_Factory::INSERT()
        ));
    }
    
    public function getSQLQuery(){
    	return $this->sqlQuery;
    }
	
}
