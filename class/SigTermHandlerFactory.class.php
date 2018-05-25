<?php

class SigTermHandlerFactory {

	public function getNewInstance(){
		return new SigTermHandler();
	}
}