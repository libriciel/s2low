<?php

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
