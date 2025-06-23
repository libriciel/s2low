<?php

use S2lowLegacy\Model\AuthorityTypesSQL;

class AuthorityTypesSQLTest extends S2lowTestCase
{
    public function testGetInfo()
    {
        $authoritiesTypeSQL = self::getContainer()->get(AuthorityTypesSQL::class);
        $this->assertEquals("Conseil régional", $authoritiesTypeSQL->getInfo(11)['description']);
    }
}
