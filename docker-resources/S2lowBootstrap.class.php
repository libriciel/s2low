<?php


class S2lowBootstrap {

	private $sqlQuery;

	public function __construct(SQLQuery $sqlQuery, PostgreSQLController $postgreSQLController) {
		$this->sqlQuery = $sqlQuery;
		$this->postgreSQLController = $postgreSQLController;
	}

	public function bootstrap(){
		$this->log("Initialisation de S2low");
		try {
			$this->installCertificate();
			$this->installHorodateur();
			$this->installLibersign();
			$this->sqlQuery->waitStarting(function($m)  {echo "$m\n";});
			$this->dbUpdate();
			$this->insertDemoS();
			$this->populateDatabase();
		} catch (Exception $e){
			$this->log("Erreur : " . $e->getMessage());
		}
	}

	private function installCertificate(){
		if (file_exists("/etc/apache2/ssl/privkey.pem")){
			$this->log("Le certificat du site est déjà présent.");
			return;
		}

		$hostname = $this->getHostname();

		$letsencrypt_cert_path = "/etc/letsencrypt/live/$hostname";
		$privkey_path  = "$letsencrypt_cert_path/privkey.pem";
		$cert_path  = "$letsencrypt_cert_path/fullchain.pem";
		if (file_exists($privkey_path)){
			$this->log("Certificat letsencrypt trouvé !");
			symlink($privkey_path,"/etc/apache2/ssl/privkey.pem");
			symlink($cert_path,"/etc/apache2/ssl/fullchain.pem");
			return;
		}

		$script = __DIR__."/../docker-resources/certificate/generate-key-pair.sh";

		exec("$script $hostname",$output,$return_var);
		$this->log(implode("\n",$output));
		if ($return_var != 0){
			throw new Exception("Impossible de générer ou de trouver le certificat du site $hostname !");
		}
	}

	private function dbUpdate(){
		$this->postgreSQLController->alterDatabase(function($message){$this->log($message);});
	}

	private function insertDemos(){

		if ($this->sqlQuery->queryOne("SELECT * FROM users WHERE role='SADM'")){
			$this->log("L'utilisateur admin existe déjà");
			return;
		}

		$authority_id = $this->sqlQuery->queryOne(
			"INSERT INTO authorities (id, status, name) VALUES(nextval('authorities_id_seq'), 1, 'Administrateurs') RETURNING id"
		);
		$this->log("Création de l'utilisateur admin [certificat DEMO-SUPER Adullact G3]");

		$him = new User();

		$him->set("name", "admin");
		$him->set("givenname", "admin");
		$him->set("email", "noreply@libriciel.coop");
		$him->set("status", 1);
		$him->set("authority_id", $authority_id);
		$him->set("role", 'SADM');

		$him->set("certFilePath", __DIR__."/certificate/demosuper.pem");
		if (! $him->save()) {
			throw new Exception("Erreur lors de l'enregistrement de l'utilisateur : " . $him->getErrorMsg());
		}

		$user_id = $him->getId();

		$userSQL = new UserSQL($this->sqlQuery);
		$userSQL->saveCertificateRGS2Etoiles($user_id,"");

		$this->log("Utilisateur créé avec succès");
	}

	public function populateDatabase(){
		$data = file_get_contents(__DIR__."/database/database_populate.json");
		$all = json_decode($data,true);
		foreach($all as $table => $table_definition){
			foreach($table_definition as $line){
				$sql = "SELECT * " .
						" FROM $table WHERE id=?";
				if ($this->sqlQuery->queryOne($sql,$line['id'])){
					continue;
				}
				$all_id = array();
				$all_value = array();
				$point = array();
				foreach($line as $id => $value){
					$all_id[] = $id;
					if ($value === '' ){
						$all_value[] = null;
					} else if ($value === "0"){
						$all_value[] = 0;
					} else {
						$all_value[] = utf8_decode($value) ?: '';
					}
					$point[] = "?";
				}
				$all_id = implode(",",$all_id);
				$point = implode(",",$point);
				$sql2 = "INSERT INTO $table ($all_id) VALUES ($point)";
				$this->log($sql2);
				$this->sqlQuery->query($sql2,$all_value);
			}
		}

	}

	public function installHorodateur(){
		$key_file = TIMESTAMPING_PRIV_KEY;
		$cert_file = TIMESTAMPING_CERT;

		if (file_exists($cert_file)){
			$this->log("Certificat de l'horodateur déjà présent");
			return;
		}
		$this->log("Création des certificat d'horodatage");
		$hostname = $this->getHostname();

		$script = __DIR__."/certificate/generate-timestamp-certificate.sh $hostname $key_file $cert_file 2>&1";

		exec("$script ",$output,$return_var);
		$this->log(implode("\n",$output));
		if ($return_var != 0){
			throw new Exception("Impossible de générer le certificat du timestamp !");
		}

		file_put_contents(TIMESTAMPING_PRIV_KEY_PASS,"");
		
		$username='www-data';
		chown($key_file,$username);
		chown($cert_file,$username);
		
		$this->log("Certificat d'horodatage créé");
	}

	public function installLibersign(){
		if (file_exists(__DIR__."/../public.ssl/libersign/update.json")){
			$this->log("Libersign est déjà installé");
			return true;
		}
		if (empty(LIBERSIGN_INSTALLER)){
			$this->log("Lien vers l'installeur de Libersign non trouvée");
			return true;
		}
		return $this->majLibersign();
	}

	public function majLibersign(){
		$this->log("Installation de Libersign");
		$make = file_get_contents(LIBERSIGN_INSTALLER);
		file_put_contents("/tmp/libersign_make.sh",$make);
		exec("/bin/bash /tmp/libersign_make.sh PROD",$output,$result);
		return true;
	}

	private function log($message){
		echo "[".date("Y-m-d H:i:s")."][S2LOW bootstrap] $message\n";
	}

	private function getHostname(){
		return parse_url(WEBSITE_SSL,PHP_URL_HOST);
	}
}