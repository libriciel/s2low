<?php

use PHPUnit\Framework\TestCase;

class ActesFilesForSAETest extends TestCase {

	public function getCase(){
		return [
			[['a.pdf'],['a.pdf']],
			[['vide.pdf','vide.pdf','vide.pdf'],['vide.pdf','vide_1.pdf','vide_2.pdf']],
			[['a.pdf','b.pdf','c.pdf'],['a.pdf','b.pdf','c.pdf']],
			[['a.pdf','b.pdf','a.pdf'],['a.pdf','b.pdf','a_1.pdf']],
			[['a','b','a'],['a','b','a_1']],
		];
	}

	/**
	 * @dataProvider getCase
	 */
	public function testOne($input,$expected){
		$actesFiles = new ActesFilesForSAE();

		$actesFiles->actes_filename= array_shift($input);

		$actesFiles->annexe = array_map(function ($a){return ['filename'=>$a];},$input);


		$actesFiles->renameSameFilename();

		$this->assertEquals(array_shift($expected),$actesFiles->actes_filename);
		for($i=0;$i<count($expected);$i++){
			$this->assertEquals(
				['filename'=>$expected[$i]],
				$actesFiles->annexe[$i]
			);
		}
	}

}