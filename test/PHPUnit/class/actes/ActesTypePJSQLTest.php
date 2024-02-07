<?php

declare(strict_types=1);

namespace PHPUnit\class\actes;

use Exception;
use PHPUnit\S2lowTestCase;
use S2lowLegacy\Class\actes\ActesTypePJSQL;
use S2lowLegacy\Class\actes\ActesUpdateClassificationSQL;

class ActesTypePJSQLTest extends S2lowTestCase
{
    /**
     * @throws Exception
     */
    public function testgetAllByNature()
    {
        $actesUpdateClassificationSQL = $this->getObjectInstancier()->get(ActesUpdateClassificationSQL::class);

        $actesUpdateClassificationSQL->updateClassification(
            "123456789",
            file_get_contents(__DIR__ . "/fixtures/classification-exemple.xml"),
        );

        $actesTypePJSQL = $this->getObjectInstancier()->get(ActesTypePJSQL::class);
        $all_typologie = $actesTypePJSQL->getAllByNature();
        $this->assertEquals("Avis de l'autorité compétente de l'État", $all_typologie[4][3][2]['32_AA']);
    }

    /**
     * @throws Exception
     */
    public function testgetListByNature()
    {
        $actesUpdateClassificationSQL = $this->getObjectInstancier()->get(ActesUpdateClassificationSQL::class);

        $actesUpdateClassificationSQL->updateClassification(
            "123456789",
            file_get_contents(__DIR__ . "/fixtures/classification-exemple.xml")
        );

        $actesTypePJSQL = $this->getObjectInstancier()->get(ActesTypePJSQL::class);
        $all_typologie = $actesTypePJSQL->getListByNature();

        $this->assertEquals("Avis de l'autorité compétente de l'État (32_AA)", $all_typologie[4]['32_AA']);
        $this->assertEquals("Délibération (99_DE)", reset($all_typologie[1]));
    }
}
