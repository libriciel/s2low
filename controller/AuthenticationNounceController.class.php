<?php

class AuthenticationNounceController extends Controller {

	public function _actionAfter(){
		/* Nothing to do*/
	}

	public function getAction(){
		$this->verifUser();
		if (empty($_SERVER['PHP_AUTH_USER'])){
			header("HTTP/1.1 401 Unauthorized");
			header('WWW-Authenticate: Basic realm="API S2low"');
			echo "La fonction n'est utilisable qu'avec un login+mot de passe HTTP";
			return false;
		}
		/** @var NounceSQL $nounceSQL */
		$nounceSQL = $this->getObjectInstancier()->get('NounceSQL');
		$authority_id = $this->me->get('authority_id');
		$nounce = $nounceSQL->create($_SERVER['PHP_AUTH_USER'],$_SERVER['PHP_AUTH_PW'],$authority_id);

		echo $nounce;
		return true;
	}

}