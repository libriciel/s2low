<?php

class ActesClassificationCodesSQLTest extends S2lowTestCase {

	/**
	 * @return PHPUnit_Extensions_Database_DataSet_IDataSet
	 */
	public function getDataSet() {
		return new PHPUnit_Extensions_Database_DataSet_YamlDataSet( __DIR__."/database_classification.yml");
	}

	public function testGetDescription(){
		$actesClassificationCodesSQL = new ActesClassificationCodesSQL($this->getSQLQuery());
		$this->assertEquals("toto",$actesClassificationCodesSQL->getDescription(1,array(1,2,3)));
	}

	public function testGetDescriptionNotExists(){
		$actesClassificationCodesSQL = new ActesClassificationCodesSQL($this->getSQLQuery());
		$this->assertEquals("toto",$actesClassificationCodesSQL->getDescription(1,array(1,2,3,1)));
	}

	public function testgetAllDescription(){
        $actesClassificationCodesSQL = new ActesClassificationCodesSQL($this->getSQLQuery());
        $result = $actesClassificationCodesSQL->getAllDescription(1);
        $this->assertEquals("toto",$result[1]['children'][2]['children'][3]['description']);
    }

}