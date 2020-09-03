<?php

use PHPUnit\Framework\MockObject\MockObject;

class FilesOnFtpTest extends S2lowTestCase {
    public function testNoFile(){

        /** @var FTPService | MockObject */
        $ftpTemp = $this->getMockBuilder(FTPService::class)->disableOriginalConstructor()->getMock();
        $ftpTemp->method('getFiles')->willReturn([]);
        $ftpTemp->expects($this->once())->method('connect');
        $ftpTemp->expects($this->once())->method('disconnect');
        $ftpTemp->expects($this->never())->method('retrieveFile');
        $files = new FTPHeliosReceiver($ftpTemp);
        $files->retrieveNames();

        $filesOnFtp = [];
        foreach($files as $file){
            $filesOnFtp[] = $file;
        }
        $this->assertCount(0,$filesOnFtp);
    }

    public function testThreeFiles(){
        /** @var FTPService | MockObject */
        $ftpTemp = $this->getMockBuilder(FTPService::class)->disableOriginalConstructor()->getMock();
        $ftpTemp->method('getFiles')->willReturn(["Test1","Test2","Test3"]);
        $ftpTemp->expects($this->once())->method('connect');
        $ftpTemp->expects($this->once())->method('disconnect');
        $ftpTemp->expects($this->exactly(3))->method('retrieveFile');
        $files = new FTPHeliosReceiver($ftpTemp);
        $files->retrieveNames();

        $filesOnFtp = [];
        foreach($files as $file){
            echo "\n file : $file";
            $filesOnFtp[] = $file;
        }

        $this->assertCount(3,$filesOnFtp);
    }

    public function testThreeFilesOnePesAller(){
        /** @var FTPService | MockObject */
        $ftpTemp = $this->getMockBuilder(FTPService::class)->disableOriginalConstructor()->getMock();
        $ftpTemp->method('getFiles')->willReturn(["Test1","PESALR2_Test2","Test3"]);
        $ftpTemp->expects($this->once())->method('connect');
        $ftpTemp->expects($this->once())->method('disconnect');
        $files = new FTPHeliosReceiver($ftpTemp);
        $files->retrieveNames();

        $filesOnFtp = [];
        foreach($files as $file){
            echo "\n file : $file";
            $filesOnFtp[] = $file;
        }

        $this->assertCount(2,$filesOnFtp);
    }


}