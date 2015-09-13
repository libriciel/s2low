<?php

class AuthentificationTest  extends S2lowTestCase {
	
	private function authenticateWith($expected,$server = array(),$session = array()){
		$userSQL = new UserSQL($this->getSQLQuery());
		$authentification = new Authentification($server,$session,$userSQL);
		$this->assertEquals($expected,$authentification->authenticate());
	}

	public function testAuthenticate(){
		$this->setExpectedException("Exception","La connexion n'a pas pu être établie");
		$this->authenticateWith(false);
	}
	
	public function testAuthenticateCert(){
		$server['SSL_CLIENT_VERIFY'] = "SUCCESS";
		$server['SSL_CLIENT_S_DN'] = "test_subject";
		$server['SSL_CLIENT_I_DN'] = "test_issuer";
		$this->authenticateWith(1, $server);
	}

	public function testManyCertWithLogin(){
		$server['SSL_CLIENT_VERIFY'] = "SUCCESS";
		$server['SSL_CLIENT_S_DN'] = "adullact";
		$server['SSL_CLIENT_I_DN'] = "adullact";
		$this->setExpectedException("Exception","La connexion n'a pas pu être établie");
		$this->authenticateWith(false, $server);
	}
	
	public function testManyCertLoginOk(){
		$server['SSL_CLIENT_VERIFY'] = "SUCCESS";
		$server['SSL_CLIENT_S_DN'] = "adullact";
		$server['SSL_CLIENT_I_DN'] = "adullact";
		$session['id_login'] = 2;
		$this->authenticateWith(2, $server,$session);		
	}
	
	public function testManyCertLoginWithLogin(){
		$server['SSL_CLIENT_VERIFY'] = "SUCCESS";
		$server['SSL_CLIENT_S_DN'] = "adullact";
		$server['SSL_CLIENT_I_DN'] = "adullact";
		$server['PHP_AUTH_USER'] = "alice";
		$server['PHP_AUTH_PW'] = "alice";
		$this->authenticateWith(2, $server);
	}

	public function testBadLogin(){
		$server['SSL_CLIENT_VERIFY'] = "SUCCESS";
		$server['SSL_CLIENT_S_DN'] = "adullact";
		$server['SSL_CLIENT_I_DN'] = "adullact";
		$server['PHP_AUTH_USER'] = "alice";
		$server['PHP_AUTH_PW'] = "bad password";
		$this->setExpectedException("Exception","La connexion n'a pas pu être établie");
		$this->authenticateWith(false, $server);
	}

	public function testNotLoginWithForwardCertificate(){
		$server['SSL_CLIENT_VERIFY'] = "SUCCESS";
		$server['SSL_CLIENT_S_DN'] = "adullact_identification";
		$server['SSL_CLIENT_I_DN'] = "adullact_identification";
		$this->setExpectedException("Exception","La connexion n'a pas pu être établie");
		$this->authenticateWith(false, $server);
	}
	
	public function testLoginWithForwardCertificate(){
		$server['SSL_CLIENT_VERIFY'] = "SUCCESS";
		$server['SSL_CLIENT_S_DN'] = "adullact_identification";
		$server['SSL_CLIENT_I_DN'] = "adullact_identification";
		$server['HTTP_ORG_S2LOW_FORWARD_X509_IDENTIFICATION'] = 'MIIFeTCCA2ECAQgwDQYJKoZIhvcNAQEFBQAwgYoxCzAJBgNVBAYTAkZSMQ8wDQYDVQQIDAZGcmFuY2UxDTALBgNVBAcMBEx5b24xETAPBgNVBAoMCFNpZ21hbGlzMSYwJAYDVQQDDB1TaWdtYWxpcyBDZXJ0aWZpY2F0ZSBBdXRvcml0eTEgMB4GCSqGSIb3DQEJARYRZXJpY0BzaWdtYWxpcy5jb20wHhcNMTUwODE5MDgzMzU5WhcNMjUwODE2MDgzMzU5WjB6MQswCQYDVQQGEwJGUjEPMA0GA1UECAwGRnJhbmNlMQ0wCwYDVQQHDARMeW9uMREwDwYDVQQKDAhTaWdtYWxpczERMA8GA1UECwwIc2lnbWFsaXMxJTAjBgNVBAMMHEVyaWNfUG9tbWF0ZWF1X1JHU18yX2V0b2lsZXMwggIiMA0GCSqGSIb3DQEBAQUAA4ICDwAwggIKAoICAQDYdUMag6AQO7uepqYJm1Uyi/U/zpgIm+8LpWIZJsFQj++dXcDHa+fV8TGun8H8tqVMfDwNd+VREgDiatU8v/PZDJw2ZjTETGC1qeN2eM3ZrOXvur8y8m5j1KPtT9y2M8k204NW0mf/weoYVSulEQbsyoQJfIMu7ALi/XvFXkGjvpG/BRr8MfSh7GtUtaGJhpGVTwv0gHXXGorixgGPhDNVE8Wr2mn/icfb/hpfQamO62W/fP4p1thGo5CMhqjyl6PLseU76nD9lUzWZtLSE1/1885zWsHGqD63Vhc/8Dr89GqCqKdBM4egwlQvT8diTZpeYSRCxHAybiPSAu5WUd0UMARabiQGbXrR+Rqs+C2W5WkUrwU8bwvpZlPF/BiGWAMayqA3xos7uqHFQjlNtg7wRir4dYxNH/whbl0Gu5dOMbFcFU/mqWpvEPIpIG5Ym7shoUYUH2x8T5TGvIn3a5BUCGmH9n/DH8ybNqD63hlsdQ2Bnt4n/nZfS1a326j3EA4eALeQ0vbLxWqoy7hASzkfFO7YDEi2U4rucfAXJWDq4O3HzPl+aQseken7DBLGfNobl77JKYIadJqbvDXNueTm6+l7r/okg76xCUIQr5Sp+x4YpVolt/FG6KY0LNfTnByniWwQoOXh4Az4qGuiGgV+l9gOuZRTr/KrZ6tXZ9T3iQIDAQABMA0GCSqGSIb3DQEBBQUAA4ICAQCTL+p4cZLzQJ2boA43xX/YdJRTPNKaka0BywJ5HIkFEm5YNVgxrqfoQZC+DxCqAGJwQm7+HkDZpWr2RkmloVHFrancpkcWU1Vca5jN9oPg6rMlQLiLz4hnO8XAjcYBR4neAIDd8DP5kwH/Kj36vPqu0ki5osd70G4ZpsBoW4BXVEPhwLKTBzQvZREsC+654k/JAbAYj/FQba9jaudfbO5xVLbNlhYKv+Iz+pUGJIP+Sr5wykDuWuyLwnnyvg0WQ0CeUWgG0x4D7Ef828i92ZC/CumpjaRYKMPYEzitndNW36K1CldGfCuNUqAQmqXIPZqyaqQ0H7N8BnISwdBCojZEljwfNOxHLrT1MQDDacl5gLEEYKj8JW86UiMAo3ONIi1HYT+eo4Sx/BzH9GhUwT8IUuiHnl721SzNuIRzB++VurtvQVc5cDumko6Qy+VgRnxPbzk32ortsBYUAFZUpoGA1f1BW4wgpYN8mfzmXBL88ugP7bWYQLV6wxoBW44IbLnIPJoWP8c13YWC2pC8DIOXzXONyPThsQ7QoSMwU27XzH1zb+NiD8sHNPgHacK6gSg/ZBj53IMGtElUAw3RRgXbuYnKeprALP5oks/IqINKST3K68njxMHj/v/hduEkw0dJxD5J/ga9beBhZ2Soe7XqBuUvYNN6Z4fNWGHPgI7R6w==';
		$this->authenticateWith(4, $server);
	}
	
	public function testGetInstance(){
		$authentification = Authentification::getInstance();
		$this->assertInstanceOf("Authentification",$authentification);
	}


	public function testAuthenticateWithCert(){
		$server['SSL_CLIENT_VERIFY'] = "SUCCESS";
		$server['SSL_CLIENT_S_DN'] = "adullact_identification";
		$server['SSL_CLIENT_I_DN'] = "adullact_identification";
		$server['SSL_CLIENT_CERT'] = file_get_contents(__DIR__."/fixtures/clean_pem.pem");
		$this->setExpectedException("Exception","La connexion n'a pas pu être établie");
		$this->authenticateWith(4,$server);
	}

	public function testAuthenticateWithBadCert(){
		$server['SSL_CLIENT_VERIFY'] = "SUCCESS";
		$server['SSL_CLIENT_S_DN'] = "adullact_identification";
		$server['SSL_CLIENT_I_DN'] = "adullact_identification";
		$server['SSL_CLIENT_CERT'] = "foo";
		$this->setExpectedException("Exception","La connexion n'a pas pu être établie");
		$this->authenticateWith(4,$server);
	}

	public function testAuthentificationFailed(){
		$server['SSL_CLIENT_VERIFY'] = "FAILED";
		$session['id_login'] = 2;
		$this->setExpectedException("Exception","La connexion n'a pas pu être établie");
		$this->authenticateWith(2,$server,$session);
	}

	public function testAuthentificationFailed2(){
		$server['SSL_CLIENT_VERIFY'] = "";
		$session['id_login'] = 2;
		$this->setExpectedException("Exception","La connexion n'a pas pu être établie");
		$this->authenticateWith(2,$server,$session);
	}
	
}