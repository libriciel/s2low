<?php

use S2lowLegacy\Class\actes\ActesClassificationCodesSQL;
use S2lowLegacy\Class\Database;
use S2lowLegacy\Lib\SQLQuery;

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
        $sqlQuery = self::getContainer()->get(SQLQuery::class);
        $database = self::getContainer()->get(Database::class);
        $actesClassificationCodesSQL = new ActesClassificationCodesSQL($sqlQuery);
        $this->assertEquals("toto", $actesClassificationCodesSQL->getDescription(1, array(1,2,3)));
    }

    public function testGetDescriptionNotExists()
    {
        $sqlQuery = self::getContainer()->get(SQLQuery::class);
        $database = self::getContainer()->get(Database::class);
        $actesClassificationCodesSQL = new ActesClassificationCodesSQL($sqlQuery);
        $this->assertEquals("toto", $actesClassificationCodesSQL->getDescription(1, array(1,2,3,1)));
    }

    public function testgetAllDescription()
    {
        $sqlQuery = self::getContainer()->get(SQLQuery::class);
        $database = self::getContainer()->get(Database::class);
        $actesClassificationCodesSQL = new ActesClassificationCodesSQL($sqlQuery);
        $result = $actesClassificationCodesSQL->getAllDescription(1);
        $this->assertEquals("toto", $result[1]['children'][2]['children'][3]['description']);
    }
}
