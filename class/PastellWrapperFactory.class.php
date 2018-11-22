<?php

class PastellWrapperFactory {

	private $curlWrapperFactory;
	private $s2lowLogger;


	public function __construct(
		CurlWrapperFactory $curlWrapperFactory,
		S2lowLogger $s2lowLogger
	) {
		$this->curlWrapperFactory = $curlWrapperFactory;
		$this->s2lowLogger = $s2lowLogger;
	}

	/** Sert pour les test unitaire... */
	public function getNewInstance(PastellProperties $pastellProperties){
		return new PastellWrapper(
			$pastellProperties,
			$this->curlWrapperFactory,
			$this->s2lowLogger
		);
	}

}