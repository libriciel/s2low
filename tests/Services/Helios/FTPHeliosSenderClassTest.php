<?php

namespace S2low\Tests\Services\Helios;

use S2low\Services\Helios\FTPConnection\ConnectionConfiguration;
use S2low\Services\Helios\FTPConnection\FTPServerProtocolConfiguration;
use S2low\Services\Helios\FTPConnection\FullConfiguration;
use S2low\Services\Helios\FTPConnection\ServerPathsConfiguration;
use S2low\Services\Helios\FTPHeliosSender;
use S2low\Services\Helios\HeliosConnection;
use S2low\Services\Helios\HeliosConnectionBuilder;
use S2lowTestCase;

class FTPHeliosSenderClassTest extends S2lowTestCase
{
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }

    public function testSendPstMode()
    {
        /** @var HeliosConnection | \PHPUnit\Framework\MockObject\MockObject $heliosConnection */
        $heliosConnection = $this->getMockBuilder(HeliosConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $heliosConnection->expects($this->once())->method("sendOneFileWithProperties")
            ->with("p_dest", "p_msg", "p_appli", "file_tosend");

        /** @var HeliosConnectionBuilder | \PHPUnit\Framework\MockObject\MockObject $heliosConnectionBuilder */
        $heliosConnectionBuilder = $this->getMockBuilder(HeliosConnectionBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $heliosConnectionBuilder->expects($this->once())->method("connect")->willReturn($heliosConnection);

        $ftpHeliosSender = new FTPHeliosSender(
            $heliosConnectionBuilder,
            new FullConfiguration(
                new ConnectionConfiguration("", "", "", "", "", "ftp"),
                new FTPServerProtocolConfiguration("", ""),
                new ServerPathsConfiguration("", "", "")
            ),
            "p_appli"
        );

        $ftpHeliosSender->sendFile("p_dest", "p_msg", "file_tosend");
    }

    public function testSendNotPstMode()
    {
        /** @var HeliosConnection | \PHPUnit\Framework\MockObject\MockObject $heliosConnection */
        $heliosConnection = $this->getMockBuilder(HeliosConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $heliosConnection->expects($this->once())->method("sendOneFileWithProperties")
            ->with("p_dest", "p_msg", "p_appli", "file_tosend");

        /** @var HeliosConnectionBuilder | \PHPUnit\Framework\MockObject\MockObject $heliosConnectionBuilder */
        $heliosConnectionBuilder = $this->getMockBuilder(HeliosConnectionBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $heliosConnectionBuilder->expects($this->once())->method("connect")->willReturn($heliosConnection);

        $ftpHeliosSender = new FTPHeliosSender(
            $heliosConnectionBuilder,
            new FullConfiguration(
                new ConnectionConfiguration("", "", "", "", "", "ftp"),
                new FTPServerProtocolConfiguration("", ""),
                new ServerPathsConfiguration("", "", "")
            ),
            "p_appli",
        );

        $ftpHeliosSender->sendFile("p_dest", "p_msg", "file_tosend");
    }
}
