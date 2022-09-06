<?php

use S2lowLegacy\Model\AuthorityTypesSQL;

class AuthorityTypesSQLTest extends S2lowTestCase
{
    public function testGetInfo()
    {
        $authoritiesTypeSQL = new AuthorityTypesSQL($this->getSQLQuery());
        $this->assertEquals("Conseil régional", $authoritiesTypeSQL->getInfo(11)['description']);
    }
}
