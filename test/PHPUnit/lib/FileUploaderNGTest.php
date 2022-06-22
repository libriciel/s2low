<?php

class FileUploaderNGTest extends PHPUnit_Framework_TestCase {

	const FILE_CONTENT="Hello World!";

	/** @var  FileUploaderNG */
	private $fileUploader;

	private $tmp_file;

	protected function setUp() : void {
		parent::setUp();
		$this->fileUploader = new FileUploaderNG();
		org\bovigo\vfs\vfsStream::setup('test');
		$testStreamUrl = org\bovigo\vfs\vfsStream::url('test');
		$this->tmp_file = $testStreamUrl."/test.text";

		file_put_contents($this->tmp_file,self::FILE_CONTENT);

		$file = array(
			"foo" =>  array(
				'name'=>'bar',
				'type'=>'text/plain',
				'size'=>42,
				'tmp_name'=>$this->tmp_file,
				'error'=>UPLOAD_ERR_OK
			),
			"foo_2" =>  array(
				'name'=>array('bar_2','bar_3'),
				'type'=>'text/plain',
				'size'=> 12,
				'tmp_name'=>array($this->tmp_file,$this->tmp_file),
				'error'=>UPLOAD_ERR_OK
			)

		);
		$this->fileUploader->setFiles($file);
	}

	public function testGetNameFailed(){
		$this->assertFalse($this->fileUploader->getName('baz'));
		$this->assertEquals(
			"Aucun fichier reÃ§u (code : Fichier baz inexistant)",
			$this->fileUploader->getLastError()
		);
	}

	public function testGetName(){
		$this->assertEquals('bar',$this->fileUploader->getName('foo'));
	}

	public function testGetMultiName(){
		$this->assertEquals('bar_3',$this->fileUploader->getName('foo_2',1));
	}

	public function testGetMultiNameNotExist(){
		$this->assertFalse($this->fileUploader->getName('foo_2',42));
	}

	public function testGetFilePath(){
		$this->assertEquals($this->tmp_file,$this->fileUploader->getFilePath('foo'));
	}

	public function testGetFileContent(){
		$this->assertEquals(self::FILE_CONTENT,$this->fileUploader->getFileContent('foo'));
	}

	public function testGetFileContentFalse(){
		$this->assertFalse($this->fileUploader->getFileContent('baz'));
	}

	public function testSave(){
		$new_filename = $this->tmp_file."_new";
		$this->fileUploader->save('foo',$new_filename);
		$this->assertEquals(self::FILE_CONTENT,file_get_contents($new_filename));
	}

	public function testGetNbFile(){
		$this->assertEquals(1,$this->fileUploader->getNbFile('foo'));
	}

	public function testGetNbFile0(){
		$this->assertEquals(0,$this->fileUploader->getNbFile('baz'));
	}

	public function testGetNbFile2(){
		$this->assertEquals(2,$this->fileUploader->getNbFile('foo_2'));
	}

	public function testGetAll(){
		$this->assertEquals('bar',$this->fileUploader->getAll()['foo']);
	}



}