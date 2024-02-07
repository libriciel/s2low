<?php

declare(strict_types=1);

namespace PHPUnit\class\actes;

use PHPUnit\S2lowTestCase;
use S2lowLegacy\Class\actes\ActesClassificationCreation;
use S2lowLegacy\Model\AuthoritySQL;

class ActesClassificationCreationTest extends S2lowTestCase
{
    public function testsendToAllAuthorities()
    {
        $authoritySQL = $this->getObjectInstancier()->get(AuthoritySQL::class);


        $authoritySQL->create("Ecully", "000000000");

        $actesClassificationCreation = new ActesClassificationCreation();
        ob_start();
        $actesClassificationCreation->sendToAllAuthorities();
        $content = ob_get_contents();
        ob_end_clean();
        $this->assertEquals("Bourg-en-Bresse - id 1 :[OK]
Saint-Andre de Corcy - id 2 :[PASS] module actes inactif
Ecully - id 4 :[PASS] collectivité inactive
", $content);
    }
}
