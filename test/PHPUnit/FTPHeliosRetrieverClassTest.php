<?php

class FTPHeliosRetrieverClassTest extends S2lowTestCase {
    public function testRetrieveEmptyRemoteDir(){
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

    public function testRetrieveNonEmptyRemoteDir(){
        /** @var $ftpService FTPService | \PHPUnit\Framework\MockObject\MockObject */
        $ftpService = $this->getMockBuilder(FTPService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $ftpService->expects($this->once())->method("connect");
        $ftpService->expects($this->once())
            ->method("getFileNames")
            ->with("response_server_path")
            ->willReturn(["File1","File2"]);
        $ftpService->expects($this->once())
            ->method("getFileNames")
            ->with("response_server_path")
            ->willReturn(["File1","File2"]);
        $ftpService->expects($this->exactly(2))
            ->method("retrieveFile")
            ->withConsecutive(["File1","tmp_local_path"],["File2","tmp_local_path"])
            ->willReturn(true);

        $receiver = new FTPHeliosReceiver(
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