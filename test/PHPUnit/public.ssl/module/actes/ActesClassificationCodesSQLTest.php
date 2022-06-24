<?php

class ActesClassificationCodesSQLTest extends S2lowTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->getSQLQuery()->query(
            "INSERT INTO actes_classification_codes(id,authority_id,level,code,parent_id,description)" .
            "   VALUES (1,1,1,1,NULL,'Commande Publique')"
        );
        $this->getSQLQuery()->query(
            "INSERT INTO actes_classification_codes(id,authority_id,level,code,parent_id,description)" .
            "   VALUES (2,1,2,2,1,'Marches publics')"
        );
        $this->getSQLQuery()->query(
            "INSERT INTO actes_classification_codes(id,authority_id,level,code,parent_id,description)" .
            "   VALUES (3,1,3,3,2,'toto')"
        );
    }

    public function testGetDescription()
    {
        $actesClassificationCodesSQL = new ActesClassificationCodesSQL($this->getSQLQuery());
        $this->assertEquals("toto", $actesClassificationCodesSQL->getDescription(1, array(1,2,3)));
    }

    public function testGetDescriptionNotExists()
    {
        $actesClassificationCodesSQL = new ActesClassificationCodesSQL($this->getSQLQuery());
        $this->assertEquals("toto", $actesClassificationCodesSQL->getDescription(1, array(1,2,3,1)));
    }

    public function testgetAllDescription()
    {
        $actesClassificationCodesSQL = new ActesClassificationCodesSQL($this->getSQLQuery());
        $result = $actesClassificationCodesSQL->getAllDescription(1);
        $this->assertEquals("toto", $result[1]['children'][2]['children'][3]['description']);
    }
}
