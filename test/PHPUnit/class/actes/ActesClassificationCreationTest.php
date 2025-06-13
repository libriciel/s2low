<?php

use S2lowLegacy\Class\actes\ActesClassificationCreation;
use S2lowLegacy\Model\AuthoritySQL;

class ActesClassificationCreationTest extends S2lowTestCase
{
    public function testsendToAllAuthorities()
    {
        $authoritySQL = self::getContainer()->get(AuthoritySQL::class);
        $id = $authoritySQL->create("Ecully", "000000000");

        $actesClassificationCreation = new ActesClassificationCreation();
        ob_start();
        $actesClassificationCreation->sendToAllAuthorities();
        $content = ob_get_contents();
        ob_end_clean();

        $this->assertStringContainsString("Bourg-en-Bresse - id 101 :[OK]", $content);
        $this->assertStringContainsString("Saint-Andre de Corcy - id 102 :[PASS] module actes inactif", $content);
        $this->assertStringContainsString("Ecully - id $id :[PASS] collectivité inactive", $content);
    }
}
