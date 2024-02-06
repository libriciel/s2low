<?php

declare(strict_types=1);

namespace S2low\Tests\Services\Helios;

use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use S2low\Services\Helios\DGFiPConnection\DGFiPConnection;
use S2low\Services\Helios\FTPHeliosReceiver;
use S2lowLegacy\Class\S2lowLogger;
use S2lowTestCase;

/**
 *
 */
class FTPHeliosReceiverTest extends S2lowTestCase
{
    /**
     * @throws \Exception
     */
    public function testRetrieveEmptyRemoteDir()
    {
        /** @var  S2lowLogger | MockObject $s2lowLogger */
        $s2lowLogger = $this->getMockBuilder(S2lowLogger::class)->disableOriginalConstructor()->getMock();

        $ftpHeliosConnection = $this->getMockBuilder(DGFiPConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $ftpHeliosConnection->expects(static::once())
            ->method('getFileNames')
            ->with()
            ->willReturn([]);

        $receiver = new FTPHeliosReceiver(
            $s2lowLogger,
            $ftpHeliosConnection,
            'tmp_local_path',
            ''
        );

        $receiver->retrieveNames();

        $retrievedNames = [];
        foreach ($receiver as $retrievedName) {
            $retrievedNames[] = $retrievedName;
        }

        $this->assertEquals([], $retrievedNames);
    }

    /**
     * @throws \Exception
     */
    public function testRetrieveOneFile()
    {
        /** @var  S2lowLogger | MockObject $s2lowLogger */
        $s2lowLogger = $this->getMockBuilder(S2lowLogger::class)->disableOriginalConstructor()->getMock();

        $heliosConnection = $this->getMockBuilder(DGFiPConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $heliosConnection->expects(static::once())
            ->method('getFileNames')
            ->with()
            ->willReturn(['File']);
        $heliosConnection->expects(static::once())
            ->method('retrieveFile')
            ->with('File', 'tmp_local_path');

        $receiver = new FTPHeliosReceiver(
            $s2lowLogger,
            $heliosConnection,
            'tmp_local_path',
            ''
        );

        $receiver->retrieveNames();

        $retrievedNames = [];
        foreach ($receiver as $retrievedName) {
            $retrievedNames[] = $retrievedName;
        }

        static::assertEquals(['File'], $retrievedNames);
    }

    /**
     * @throws \Exception
     */
    public function testRetrieveNonEmptyOnePesAller()
    {
        /** @var  S2lowLogger | MockObject $s2lowLogger */
        $s2lowLogger = $this->getMockBuilder(S2lowLogger::class)->disableOriginalConstructor()->getMock();
        $s2lowLogger->expects(static::once())
            ->method('info')
            ->with('PESALR2_File : PES ALLER ignoré');

        /** @var DGFiPConnection | MockObject $heliosConnection */
        $heliosConnection = $this->getMockBuilder(DGFiPConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $heliosConnection->expects(static::once())
            ->method('getFileNames')
            ->with()
            ->willReturn(['PESALR2_File']);
        $heliosConnection->expects(static::never())
            ->method('retrieveFile');

        $receiver = new FTPHeliosReceiver(
            $s2lowLogger,
            $heliosConnection,
            'tmp_local_path',
            ''
        );

        $receiver->retrieveNames();

        $retrievedNames = [];
        foreach ($receiver as $retrievedName) {
            $retrievedNames[] = $retrievedName;
        }

        static::assertEquals([], $retrievedNames);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testRetrieveNonEmptyOnePError()
    {
        /** @var  S2lowLogger | MockObject $s2lowLogger */
        $s2lowLogger = $this->getMockBuilder(S2lowLogger::class)->disableOriginalConstructor()->getMock();
        $s2lowLogger->expects(static::once())
            ->method('info')
            ->with('0 : File récupéré : ECHEC Une très bonne raison');

        /** @var DGFiPConnection | MockObject $heliosConnection */
        $heliosConnection = $this->getMockBuilder(DGFiPConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $heliosConnection->expects(static::once())
            ->method('getFileNames')
            ->with()
            ->willReturn(['File']);
        $heliosConnection->expects(static::once())
            ->method('retrieveFile')
            ->willThrowException(new Exception('Une très bonne raison'));

        $receiver = new FTPHeliosReceiver(
            $s2lowLogger,
            $heliosConnection,
            'tmp_local_path',
            ''
        );

        $receiver->retrieveNames();

        $retrievedNames = [];
        foreach ($receiver as $retrievedName) {
            $retrievedNames[] = $retrievedName;
        }

        static::assertEquals(['File'], $retrievedNames);
    }

    /**
     * @throws Exception
     */
    public function testRetrieveMultipleFiles()
    {
        /** @var  S2lowLogger | MockObject $s2lowLogger */
        $s2lowLogger = $this->getMockBuilder(S2lowLogger::class)->disableOriginalConstructor()->getMock();

        /** @var DGFiPConnection | MockObject $heliosConnection */
        $heliosConnection = $this->getMockBuilder(DGFiPConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $heliosConnection->expects(static::once())
            ->method('getFileNames')
            ->with()
            ->willReturn(['File1', 'File2']);
        $heliosConnection->expects(static::exactly(2))
            ->method('retrieveFile')
            ->withConsecutive(['File1', 'tmp_local_path'], ['File2', 'tmp_local_path']);

        $receiver = new FTPHeliosReceiver(
            $s2lowLogger,
            $heliosConnection,
            'tmp_local_path',
            ''
        );

        $receiver->retrieveNames();

        $retrievedNames = [];
        foreach ($receiver as $retrievedName) {
            $retrievedNames[] = $retrievedName;
        }

        static::assertEquals(['File1', 'File2'], $retrievedNames);
    }
}
