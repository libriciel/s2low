<?php

declare(strict_types=1);

namespace PHPUnit\class\helios;

use PHPUnit\Framework\TestCase;
use S2lowLegacy\Class\helios\HeliosSAEDateManager;

class HeliosSAEDateManagerTest extends TestCase
{
    /** @dataProvider provider */
    public function testGetDayBeforeWhichWeDestroy(int $retention_fichiers_nb_jours, string $dateModifier)
    {
        $heliosSAEDateManager = new HeliosSAEDateManager($retention_fichiers_nb_jours);
        $testedDate = (new \DateTime())->modify($dateModifier);
        $this->assertEquals(
            ($testedDate)->format("Y-m-d"),
            $heliosSAEDateManager->getDateBeforeWhichWeDestroy()
        );
    }

    public function provider()
    {
        return [
            [0, "+ 0 day"],      // 0 jours de rétention, on supprime jusque ajd
            [-1, "+1 day"],     // -1 jour de rétention, on supprime jusque demain
            [1, "-1 day"]       // 1 jour de rétention, on supprime  jusque hier
        ];
    }
}
