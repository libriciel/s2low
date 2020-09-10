<?php

class FTPHeliosSenderClassTest extends S2lowTestCase {

    public function testSendPstMode(){
        /** @var $ftpService FTPService | \PHPUnit\Framework\MockObject\MockObject */
        $ftpService = $this->getMockBuilder(FTPService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $ftpService->expects($this->once())->method("connect");
        $ftpService->expects($this->once())->method("sendRawCommand")
            ->with("site meta P_DEST=p_dest;P_APPLI=p_appli;P_MSG=p_msg");

        $ftpHeliosSender = new FTPHeliosSender(
            $ftpService,
            true,
            "p_appli",
            "destination",
            false
        );

        $ftpHeliosSender->sendFile("p_dest","p_msg","file_tosend");

    }

    public function testSendNotPstMode(){
        /** @var $ftpService FTPService | \PHPUnit\Framework\MockObject\MockObject */
        $ftpService = $this->getMockBuilder(FTPService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $ftpService->expects($this->once())->method("connect");
        $ftpService->expects($this->exactly(3))->method("sendRawCommand")
            ->withConsecutive(["site P_DEST p_dest"],["site P_APPLI p_appli"],["site P_MSG p_msg"]);

        $ftpHeliosSender = new FTPHeliosSender(
            $ftpService,
            false,
            "p_appli",
            "destination",
            true
        );

        $ftpHeliosSender->sendFile("p_dest","p_msg","file_tosend");

    }
}