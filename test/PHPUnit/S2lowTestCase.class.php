<?php


abstract class S2lowTestCase extends PHPUnit_Extensions_Database_TestCase {
	
	private $objectInstancier;	
	private $sqlQuery;
		
	public function __construct($name = NULL, array $data = array(), $dataName = ''){
		parent::__construct($name,$data,$dataName);
		$sqlQuery = new SQLQuery(DB_DATABASE_TEST);
		$sqlQuery->setCredential(DB_USER_TEST,DB_PASSWORD_TEST);
		$sqlQuery->setDatabaseHost(DB_HOST_TEST);
		
		$this->databaseConnection = $this->createDefaultDBConnection($sqlQuery->getPdo(), DB_DATABASE_TEST);
		$this->sqlQuery = $sqlQuery;
		$this->objectInstancier = new ObjectInstancier();
		$this->objectInstancier->SQLQuery = $sqlQuery;
		
		//C'est utilisé pour les vieux truc User qui authentifie à l'aide d'un singleton...
		global $sqlQuery;
		$sqlQuery = $this->getSQLQuery();
	}
	
	protected function setUp(){
		parent::setUp();
		$_GET = array();
		$_POST = array();
		$_SESSION = array();
		$_SERVER['SSL_CLIENT_VERIFY'] = "";
		$_SERVER['SSL_CLIENT_S_DN'] = "";
		$_SERVER['SSL_CLIENT_I_DN'] = "";
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
    
    public function getObjectInstancier(){
    	return $this->objectInstancier;
    }
    
    public function getSQLQuery(){
    	return $this->sqlQuery;
    }
    
    public function setSuperAdminAuthentication(){
    	$_SERVER['SSL_CLIENT_VERIFY'] = "SUCCESS";
    	$_SERVER['SSL_CLIENT_S_DN'] = "test_subject";
    	$_SERVER['SSL_CLIENT_I_DN'] = "test_issuer";
    }
    
    public function setAdminGroupAuthentication(){
    	$_SERVER['SSL_CLIENT_VERIFY'] = "SUCCESS";
    	$_SERVER['SSL_CLIENT_S_DN'] = "admin_groupe";
    	$_SERVER['SSL_CLIENT_I_DN'] = "admin_groupe";
    }
    
    public function setAdminCol2Authentication(){
    	$_SERVER['SSL_CLIENT_VERIFY'] = "SUCCESS";
    	$_SERVER['SSL_CLIENT_S_DN'] = "admin_col2";
    	$_SERVER['SSL_CLIENT_I_DN'] = "admin_col2";
    }
	
}
