<?php

declare(strict_types=1);

namespace S2low\Tests\Services\Helios;

use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use S2low\Services\Helios\DGFiPConnection\DGFiPConnection;
use S2low\Services\Helios\FTPHeliosReceiver;
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
        /** @var  LoggerInterface | MockObject $s2lowLogger */
        $s2lowLogger = $this->getMockBuilder(LoggerInterface::class)->disableOriginalConstructor()->getMock();

        $ftpHeliosConnection = $this->getMockBuilder(DGFiPConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $ftpHeliosConnection->expects(static::exactly(2))
            ->method('getFileNames')
            ->with()
            ->willReturn([]);

        $receiver = new FTPHeliosReceiver(
            $s2lowLogger,
            $ftpHeliosConnection,
            'tmp_local_path',
            ''
        );

        $retrievedNames = $receiver->retrieveNames(0);

        foreach ($retrievedNames as $name) {
            $receiver->recupOneFile($name);
        }

        $this->assertEquals([], $retrievedNames);
    }

    /**
     * @throws \Exception
     */
    public function testRetrieveADirectoryWithOneFile()
    {
        /** @var  LoggerInterface | MockObject $s2lowLogger */
        $s2lowLogger = $this->getMockBuilder(LoggerInterface::class)->disableOriginalConstructor()->getMock();

        $heliosConnection = $this->getMockBuilder(DGFiPConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $heliosConnection->expects(static::exactly(2))
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

        $retrievedNames = $receiver->retrieveNames(0);

        foreach ($retrievedNames as $name) {
            $receiver->recupOneFile($name);
        }

        static::assertEquals(['File'], $retrievedNames);
    }

    /**
     * @throws \Exception
     */
    public function testRetrieveNonEmptyOnePesAller()
    {
        /** @var  LoggerInterface | MockObject $s2lowLogger */
        $s2lowLogger = $this->getMockBuilder(LoggerInterface::class)->disableOriginalConstructor()->getMock();
        $s2lowLogger->expects(static::once())
            ->method('info')
            ->with('PESALR2_File : PES ALLER ignoré');

        /** @var DGFiPConnection | MockObject $heliosConnection */
        $heliosConnection = $this->getMockBuilder(DGFiPConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $heliosConnection->expects(static::exactly(2))
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

        $retrievedNames = $receiver->retrieveNames(0);

        foreach ($retrievedNames as $name) {
            $receiver->recupOneFile($name);
        }

        static::assertEquals([], $retrievedNames);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testRetrieveNonEmptyOnePError()
    {
        /** @var  LoggerInterface | MockObject $s2lowLogger */
        $s2lowLogger = $this->getMockBuilder(LoggerInterface::class)->disableOriginalConstructor()->getMock();
        $s2lowLogger->expects(static::once())
            ->method('error')
            ->with(
                'Téléchargement échoué',
                [
                    'file' => 'File',
                    'remote_directory' => 'remote_directory',
                    'exception' => new Exception('Une très bonne raison')
                                ]
            );

        /** @var DGFiPConnection | MockObject $heliosConnection */
        $heliosConnection = $this->getMockBuilder(DGFiPConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $heliosConnection->expects(static::exactly(2))
            ->method('getFileNames')
            ->with()
            ->willReturn(['File']);
        $heliosConnection->expects(static::once())
            ->method('retrieveFile')
            ->willThrowException(new Exception('Une très bonne raison'));

        $heliosConnection->expects(static::once())
            ->method('pwd')
            ->willReturn('remote_directory');

        $receiver = new FTPHeliosReceiver(
            $s2lowLogger,
            $heliosConnection,
            'tmp_local_path',
            ''
        );

        $retrievedNames = $receiver->retrieveNames(0);

        $exceptionsThrown = [];
        foreach ($retrievedNames as $name) {
            try {
                $receiver->recupOneFile($name);
            } catch (Exception $exception) {
                $exceptionsThrown[] = $exception->getMessage();
            }
        }
        static::assertEquals(['File'], $retrievedNames);
        static::assertSame(['Une très bonne raison'], $exceptionsThrown);
    }

    /**
     * @throws Exception
     */
    public function testRetrieveMultipleFiles()
    {
        /** @var  LoggerInterface | MockObject $s2lowLogger */
        $s2lowLogger = $this->getMockBuilder(LoggerInterface::class)->disableOriginalConstructor()->getMock();

        /** @var DGFiPConnection | MockObject $heliosConnection */
        $heliosConnection = $this->getMockBuilder(DGFiPConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $heliosConnection->expects(static::exactly(2))
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

        $retrievedNames = $receiver->retrieveNames(0);

        foreach ($retrievedNames as $name) {
            $receiver->recupOneFile($name);
        }

        static::assertEquals(['File1', 'File2'], $retrievedNames);
    }

    /**
     * @throws Exception
     */
    public function testRetrieveFileNamesWhilePESAreDropped()
    {
        /** @var  LoggerInterface | MockObject $s2lowLogger */
        $s2lowLogger = $this->getMockBuilder(LoggerInterface::class)->disableOriginalConstructor()->getMock();

        /** @var DGFiPConnection | MockObject $heliosConnection */
        $heliosConnection = $this->getMockBuilder(DGFiPConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $heliosConnection->expects(static::exactly(4))
            ->method('getFileNames')
            ->with()
            ->willReturnOnConsecutiveCalls(
                ['File1', 'File2'],
                ['File1', 'File2', 'File3'],
                ['File1', 'File2', 'File3', 'File4'],
                ['File1', 'File2', 'File3', 'File4']
            );

        $receiver = new FTPHeliosReceiver(
            $s2lowLogger,
            $heliosConnection,
            'tmp_local_path',
            ''
        );

        $retrievedNames = $receiver->retrieveNames(0);

        static::assertEquals(['File1', 'File2', 'File3', 'File4'], $retrievedNames);
    }

    /**
     * @throws \Exception
     */
    public function testExceptionOnOneFile()
    {
        /** @var  LoggerInterface | MockObject $s2lowLogger */
        $s2lowLogger = $this->getMockBuilder(LoggerInterface::class)->disableOriginalConstructor()->getMock();

        $heliosConnection = $this->getMockBuilder(DGFiPConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $heliosConnection->expects(static::once())
            ->method('retrieveFile')
            ->with('File', 'tmp_local_path')
            ->willThrowException(new Exception('obviously not because of the remote server'));

        $receiver = new FTPHeliosReceiver(
            $s2lowLogger,
            $heliosConnection,
            'tmp_local_path',
            ''
        );

        self ::expectException(Exception::class);
        self ::expectExceptionMessage('obviously not because of the remote server');

        $receiver->recupOneFile('File');
    }

    /**
     * @throws \Exception
     */
    public function testRetrieveOneFile()
    {
        /** @var  LoggerInterface | MockObject $s2lowLogger */
        $s2lowLogger = $this->getMockBuilder(LoggerInterface::class)->disableOriginalConstructor()->getMock();

        $heliosConnection = $this->getMockBuilder(DGFiPConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $heliosConnection->expects(static::once())
            ->method('retrieveFile')
            ->with('File', 'tmp_local_path');

        $receiver = new FTPHeliosReceiver(
            $s2lowLogger,
            $heliosConnection,
            'tmp_local_path',
            ''
        );

        $receiver->recupOneFile('File');
    }
}
