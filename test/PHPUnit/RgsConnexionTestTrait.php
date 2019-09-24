<?php

trait RgsConnexionTestTrait {

	public function setRGS2stars(){
		$server['SSL_CLIENT_VERIFY'] = "SUCCESS";
		$server['SSL_CLIENT_CERT'] = file_get_contents(__DIR__."/lib/fixtures/test/MyClient1.pem");

		$rgsConnexion = $this->getObjectInstancier()->get(RgsConnexion::class);

		$rgsConnexion->setServerGlobal($server);
		$rgsConnexion->setRgsValidCaPath(__DIR__."/lib/fixtures/test/");
	}

	/**
	 * @return ObjectInstancier
	 */
	abstract public function getObjectInstancier();


}