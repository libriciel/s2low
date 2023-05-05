<?php

namespace S2low\Tests\Services\Helios;

use S2low\Services\Helios\FTPHeliosReceiver;
use S2low\Services\Helios\HeliosConnection;
use S2low\Services\Helios\HeliosConnectionBuilder;
use S2lowLegacy\Class\S2lowLogger;
use S2lowTestCase;

class FTPHeliosReceiverTest extends S2lowTestCase
{
    public function testRetrieveEmptyRemoteDir()
    {
        /** @var  $s2lowLogger S2lowLogger | \PHPUnit\Framework\MockObject\MockObject */
        $s2lowLogger = $this->getMockBuilder(S2lowLogger::class)->disableOriginalConstructor()->getMock();

        $ftpHeliosConnection = $this->getMockBuilder(\S2low\Services\Helios\HeliosConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $ftpHeliosConnection->expects($this->once())
            ->method("getFileNames")
            ->with()
            ->willReturn([]);

        $receiver = new FTPHeliosReceiver(
            $s2lowLogger,
            $ftpHeliosConnection,
            "tmp_local_path"
        );

        $receiver->retrieveNames();

        $retrievedNames = [];
        foreach ($receiver as $retrievedName) {
            $retrievedNames[] = $retrievedName;
        }

        $this->assertEquals($retrievedNames, []);
    }

    public function testRetrieveOneFile()
    {
        /** @var  $s2lowLogger S2lowLogger | \PHPUnit\Framework\MockObject\MockObject */
        $s2lowLogger = $this->getMockBuilder(S2lowLogger::class)->disableOriginalConstructor()->getMock();

        $heliosConnection = $this->getMockBuilder(\S2low\Services\Helios\HeliosConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $heliosConnection->expects($this->once())
            ->method("getFileNames")
            ->with()
            ->willReturn(["File"]);
        $heliosConnection->expects($this->once())
            ->method("retrieveFile")
            ->with("File", "tmp_local_path")
            ->willReturn(true);

        $receiver = new FTPHeliosReceiver(
            $s2lowLogger,
            $heliosConnection, //$heliosConnectionBuilder,
            "tmp_local_path"
        );

        $receiver->retrieveNames();

        $retrievedNames = [];
        foreach ($receiver as $retrievedName) {
            $retrievedNames[] = $retrievedName;
        }

        $this->assertEquals($retrievedNames, ["File"]);
    }

    public function testRetrieveNonEmptyOnePesAller()
    {
        /** @var  $s2lowLogger S2lowLogger | \PHPUnit\Framework\MockObject\MockObject */
        $s2lowLogger = $this->getMockBuilder(S2lowLogger::class)->disableOriginalConstructor()->getMock();
        $s2lowLogger->expects($this->once())
            ->method("info")
            ->with("PESALR2_File : PES ALLER ignoré");

        /** @var HeliosConnection | \PHPUnit\Framework\MockObject\MockObject $heliosConnection */
        $heliosConnection = $this->getMockBuilder(HeliosConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $heliosConnection->expects($this->once())
            ->method("getFileNames")
            ->with()
            ->willReturn(["PESALR2_File"]);
        $heliosConnection->expects($this->never())
            ->method("retrieveFile");

        $receiver = new FTPHeliosReceiver(
            $s2lowLogger,
            $heliosConnection,
            "tmp_local_path"
        );

        $receiver->retrieveNames();

        $retrievedNames = [];
        foreach ($receiver as $retrievedName) {
            $retrievedNames[] = $retrievedName;
        }

        $this->assertEquals($retrievedNames, []);
    }

    public function testRetrieveNonEmptyOnePError()
    {
        /** @var  $s2lowLogger S2lowLogger | \PHPUnit\Framework\MockObject\MockObject */
        $s2lowLogger = $this->getMockBuilder(S2lowLogger::class)->disableOriginalConstructor()->getMock();
        $s2lowLogger->expects($this->once())
            ->method("info")
            ->with("0 : File récupéré : ECHEC");

        /** @var HeliosConnection | \PHPUnit\Framework\MockObject\MockObject $heliosConnection */
        $heliosConnection = $this->getMockBuilder(HeliosConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $heliosConnection->expects($this->once())
            ->method("getFileNames")
            ->with()
            ->willReturn(["File"]);
        $heliosConnection->expects($this->once())
            ->method("retrieveFile")
            ->willReturn(false);

        $receiver = new FTPHeliosReceiver(
            $s2lowLogger,
            $heliosConnection,
            "tmp_local_path"
        );

        $receiver->retrieveNames();

        $retrievedNames = [];
        foreach ($receiver as $retrievedName) {
            $retrievedNames[] = $retrievedName;
        }

        $this->assertEquals($retrievedNames, ["File"]);
    }

    public function testRetrieveMultipleFiles()
    {
        /** @var  S2lowLogger | \PHPUnit\Framework\MockObject\MockObject $s2lowLogger */
        $s2lowLogger = $this->getMockBuilder(S2lowLogger::class)->disableOriginalConstructor()->getMock();

        /** @var HeliosConnection | \PHPUnit\Framework\MockObject\MockObject $heliosConnection */
        $heliosConnection = $this->getMockBuilder(HeliosConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $heliosConnection->expects($this->once())
            ->method("getFileNames")
            ->with()
            ->willReturn(["File1", "File2"]);
        $heliosConnection->expects($this->exactly(2))
            ->method("retrieveFile")
            ->withConsecutive(["File1", "tmp_local_path"], ["File2", "tmp_local_path"])
            ->willReturn(true);

        $receiver = new FTPHeliosReceiver(
            $s2lowLogger,
            $heliosConnection,
            "tmp_local_path"
        );

        $receiver->retrieveNames();

        $retrievedNames = [];
        foreach ($receiver as $retrievedName) {
            $retrievedNames[] = $retrievedName;
        }

        $this->assertEquals($retrievedNames, ["File1", "File2"]);
    }
}
