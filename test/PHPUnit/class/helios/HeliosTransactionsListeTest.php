<?php

declare(strict_types=1);

namespace PHPUnit\class\helios;

use PHPUnit\HeliosUtilitiesTestTrait;
use PHPUnit\S2lowTestCase;
use S2lowLegacy\Class\helios\HeliosTransactionsListe;

class HeliosTransactionsListeTest extends S2lowTestCase
{
    use HeliosUtilitiesTestTrait;

    public function testGetAll()
    {
        $this->createTransaction();
        $heliosTransactionsListe  = $this->getObjectInstancier()->get(HeliosTransactionsListe::class);
        $all = $heliosTransactionsListe->getAll();
        $this->assertEquals('toto.txt', $all[0]['filename']);
    }
}
