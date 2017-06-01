<?php


class S2lowBootstrap {

	public function bootstrap($sqlQuery){
		$this->log("Initialisation de S2low");
		try {

			$this->installCertificate();
			$this->installHorodateur();
			$this->dbUpdate($sqlQuery);
			$this->insertDemou($sqlQuery);
			$this->populateDatabase($sqlQuery);
			$this->installLibersign();
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
			throw new Exception("Impossible de générer ou de trouver le certificat du site !");
		}
	}

	private function dbUpdate(SQLQuery $sqlQuery){
		$psqlSchemaInfo = new PsqlSchemaInfo($sqlQuery);
		$database_definition = $psqlSchemaInfo->getDatabaseDefinition();
		$db_definition = file_get_contents(__DIR__."/../db/s2low.sql.json");
		$file_defintion = json_decode($db_definition,true);


		$psqlDiff = new PsqlDiff();

		$diff = $psqlDiff->diff($database_definition, $file_defintion);

		foreach($diff as $query){
			$this->log("$query");
			$sqlQuery->query($query);
		}

	}

	private function insertDemoU(SQLQuery $sqlQuery){

		if ($sqlQuery->queryOne("SELECT * FROM users WHERE name='admin'")){
			$this->log("L'utilisateur admin existe déjà");
			return;
		}

		$authority_id = $sqlQuery->queryOne(
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

		$userSQL = new UserSQL($sqlQuery);
		$userSQL->saveCertificateRGS2Etoiles($user_id,"");

		$this->log("Utilisateur créé avec succès");
	}

	private function populateDatabase(SQLQuery $sqlQuery){
		$data = file_get_contents(__DIR__."/database/database_populate.json");
		$all = json_decode($data,true);
		foreach($all as $table => $table_definition){
			foreach($table_definition as $line){
				$sql = "SELECT * FROM $table WHERE id=?";
				if ($sqlQuery->queryOne($sql,$line['id'])){
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
				$sqlQuery->query($sql2,$all_value);
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
		$this->log("Installation de Libersign");
		$make = file_get_contents(LIBERSIGN_INSTALLER);
		file_put_contents("/tmp/libersign_make.sh",$make);
		exec("/bin/bash /tmp/libersign_make.sh PROD",$output,$result);
	}

	private function log($message){
		echo "[".date("Y-m-d H:i:s")."][Pastell bootstrap] $message\n";
	}

	private function getHostname(){
		return parse_url(WEBSITE_SSL,PHP_URL_HOST);
	}
}