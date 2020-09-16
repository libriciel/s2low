<?php

class FTPHeliosReceiverTest extends S2lowTestCase {
    public function testRetrieveEmptyRemoteDir(){
        /** @var  $s2lowLogger S2lowLogger | \PHPUnit\Framework\MockObject\MockObject */
        $s2lowLogger = $this->getMockBuilder(S2lowLogger::class)->disableOriginalConstructor()->getMock();

        /** @var $ftpService FTPService | \PHPUnit\Framework\MockObject\MockObject */
        $ftpService = $this->getMockBuilder(FTPService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $ftpService->expects($this->once())->method("connect");
        $ftpService->expects($this->once())
            ->method("getFileNames")
            ->with("response_server_path")
            ->willReturn([]);

        $receiver = new FTPHeliosReceiver(
            $s2lowLogger,
            $ftpService,
            "response_server_path",
            "tmp_local_path"
        );

        $receiver->retrieveNames();

        $retrievedNames = [];
        foreach ($receiver as $retrievedName){
            $retrievedNames[] = $retrievedName;
        }

        $this->assertEquals($retrievedNames,[]);
    }

    public function testRetrieveOneFile(){
        /** @var  $s2lowLogger S2lowLogger | \PHPUnit\Framework\MockObject\MockObject */
        $s2lowLogger = $this->getMockBuilder(S2lowLogger::class)->disableOriginalConstructor()->getMock();

        /** @var $ftpService FTPService | \PHPUnit\Framework\MockObject\MockObject */
        $ftpService = $this->getMockBuilder(FTPService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $ftpService->expects($this->once())->method("connect");
        $ftpService->expects($this->once())
            ->method("getFileNames")
            ->with("response_server_path")
            ->willReturn(["File"]);
        $ftpService->expects($this->once())
            ->method("retrieveFile")
            ->with("File","tmp_local_path")
            ->willReturn(true);

        $receiver = new FTPHeliosReceiver(
            $s2lowLogger,
            $ftpService,
            "response_server_path",
            "tmp_local_path"
        );

        $receiver->retrieveNames();

        $retrievedNames = [];
        foreach ($receiver as $retrievedName){
            $retrievedNames[] = $retrievedName;
        }

        $this->assertEquals($retrievedNames,["File"]);
    }

    public function testRetrieveNonEmptyOnePesAller(){
        /** @var  $s2lowLogger S2lowLogger | \PHPUnit\Framework\MockObject\MockObject */
        $s2lowLogger = $this->getMockBuilder(S2lowLogger::class)->disableOriginalConstructor()->getMock();
        $s2lowLogger->expects($this->exactly(2))
            ->method("info")
            ->withConsecutive(["Remote_path : response_server_path"],["PESALR2_File : PES ALLER ignoré"]);

        /** @var $ftpService FTPService | \PHPUnit\Framework\MockObject\MockObject */
        $ftpService = $this->getMockBuilder(FTPService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $ftpService->expects($this->once())->method("connect");
        $ftpService->expects($this->once())
            ->method("getFileNames")
            ->with("response_server_path")
            ->willReturn(["PESALR2_File"]);
        $ftpService->expects($this->never())
            ->method("retrieveFile");

        $receiver = new FTPHeliosReceiver(
            $s2lowLogger,
            $ftpService,
            "response_server_path",
            "tmp_local_path"
        );

        $receiver->retrieveNames();

        $retrievedNames = [];
        foreach ($receiver as $retrievedName){
            $retrievedNames[] = $retrievedName;
        }

        $this->assertEquals($retrievedNames,[]);
    }

    public function testRetrieveNonEmptyOnePError(){
        /** @var  $s2lowLogger S2lowLogger | \PHPUnit\Framework\MockObject\MockObject */
        $s2lowLogger = $this->getMockBuilder(S2lowLogger::class)->disableOriginalConstructor()->getMock();
        $s2lowLogger->expects($this->exactly(2))
            ->method("info")
            ->withConsecutive(["Remote_path : response_server_path"],["0 : File récupéré : ECHEC"]);

        /** @var $ftpService FTPService | \PHPUnit\Framework\MockObject\MockObject */
        $ftpService = $this->getMockBuilder(FTPService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $ftpService->expects($this->once())->method("connect");
        $ftpService->expects($this->once())
            ->method("getFileNames")
            ->with("response_server_path")
            ->willReturn(["File"]);
        $ftpService->expects($this->once())
            ->method("retrieveFile")
            ->willReturn(false);

        $receiver = new FTPHeliosReceiver(
            $s2lowLogger,
            $ftpService,
            "response_server_path",
            "tmp_local_path"
        );

        $receiver->retrieveNames();

        $retrievedNames = [];
        foreach ($receiver as $retrievedName){
            $retrievedNames[] = $retrievedName;
        }

        $this->assertEquals($retrievedNames,["File"]);
    }

    public function testRetrieveMultipleFiles(){
        /** @var  $s2lowLogger S2lowLogger | \PHPUnit\Framework\MockObject\MockObject */
        $s2lowLogger = $this->getMockBuilder(S2lowLogger::class)->disableOriginalConstructor()->getMock();

        /** @var $ftpService FTPService | \PHPUnit\Framework\MockObject\MockObject */
        $ftpService = $this->getMockBuilder(FTPService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $ftpService->expects($this->once())->method("connect");
        $ftpService->expects($this->once())
            ->method("getFileNames")
            ->with("response_server_path")
            ->willReturn(["File1","File2"]);
        $ftpService->expects($this->exactly(2))
            ->method("retrieveFile")
            ->withConsecutive(["File1","tmp_local_path"],["File2","tmp_local_path"])
            ->willReturn(true);

        $receiver = new FTPHeliosReceiver(
            $s2lowLogger,
            $ftpService,
            "response_server_path",
            "tmp_local_path"
        );

        $receiver->retrieveNames();

        $retrievedNames = [];
        foreach ($receiver as $retrievedName){
            $retrievedNames[] = $retrievedName;
        }

        $this->assertEquals($retrievedNames,["File1","File2"]);
    }
}