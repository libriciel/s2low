<?php

declare(strict_types=1);

namespace PHPUnit\class\actes;

use PHPUnit\S2lowTestCase;
use S2lowLegacy\Class\actes\ActesTransmissionWindowsSQL;

class ActesTransmissionWindowsSQLTest extends S2lowTestCase
{
    public function testCanSend()
    {
        $actesTransmissionWindowsSQL = $this->getObjectInstancier()->get(ActesTransmissionWindowsSQL::class);
        $this->assertTrue($actesTransmissionWindowsSQL->canSend(42));
    }

    public function testCantSend()
    {
        $actesTransmissionWindowsSQL = $this->getObjectInstancier()->get(ActesTransmissionWindowsSQL::class);
        $window_id = $actesTransmissionWindowsSQL->addWindow(0);
        $actesTransmissionWindowsSQL->addWindowHours($window_id, date("Y-m-d"), date("Y-m-d H:i:s", strtotime("+1 hour")));
        $this->assertFalse($actesTransmissionWindowsSQL->canSend(42));
    }

    public function testAdd()
    {
        $actesTransmissionWindowsSQL = $this->getObjectInstancier()->get(ActesTransmissionWindowsSQL::class);
        $window_id = $actesTransmissionWindowsSQL->addWindow(100);
        $actesTransmissionWindowsSQL->addWindowHours($window_id, date("Y-m-d"), date("Y-m-d H:i:s", strtotime("+1 hour")));
        $this->assertTrue($actesTransmissionWindowsSQL->canSend(10));
        $actesTransmissionWindowsSQL->addFile(99);
        $this->assertFalse($actesTransmissionWindowsSQL->canSend(99));
    }
    public function testAddWithoutWindow()
    {
        $actesTransmissionWindowsSQL = $this->getObjectInstancier()->get(ActesTransmissionWindowsSQL::class);
        $actesTransmissionWindowsSQL->addFile(100);
        $this->assertTrue($actesTransmissionWindowsSQL->canSend(100));
    }
}
