<?php

class PastellFactory {

	public function getNewInstance($url,$id_e,$login,$password){
		return new Pastell($url,$id_e,$login,$password);
	}

}