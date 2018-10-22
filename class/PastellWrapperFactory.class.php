<?php

class PastellWrapperFactory {

	/** Sert pour les test unitaire... */
	public function getNewInstance(PastellProperties $pastellProperties){
		return new PastellWrapper(
			$pastellProperties,
			new CurlWrapperFactory()
		);
	}

}