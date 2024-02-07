<?php

declare(strict_types=1);

namespace PHPUnit\model;

use PHPUnit\S2lowTestCase;
use S2lowLegacy\Model\AuthorityTypesSQL;

class AuthorityTypesSQLTest extends S2lowTestCase
{
    public function testGetInfo()
    {
        $authoritiesTypeSQL = new AuthorityTypesSQL($this->getSQLQuery());
        $this->assertEquals("Conseil régional", $authoritiesTypeSQL->getInfo(11)['description']);
    }
}
