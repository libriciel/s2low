<?php

//Liste des clients qui n'ont pas un certificat RGS

require_once( __DIR__."/../../init/init.php");

//if (! class_exists("RgsCertificate")){

	class RgsCertificate {

		private $openssl_path;
		private $validca_path;
		private $last_message;

		/**
		 * @param $openssl_path string chemin vers l'executable OpenSSL
		 * @param $validca_path string chemin vers un répertoire contenant des autorités de certification "hasher" : man c_rehash
		 */
		public function __construct($openssl_path,$validca_path){
			$this->validca_path = $validca_path;
			$this->openssl_path = $openssl_path;
		}

		public function getLastMessage(){
			return $this->last_message;
		}

		public function isRgsCertificate($x509_pem_certificate){
			$tmp_file = "/tmp/s2low-lib-rgscertificate-".mt_rand(0,mt_getrandmax()).".pem";
			file_put_contents($tmp_file,$x509_pem_certificate);

			//Il semble qu'il n'y a pas de fonction php openssl_* qui permettent la vérification d'un certificat
			$command = "cat $tmp_file | {$this->openssl_path} verify -verbose -CApath {$this->validca_path} 2>&1";
			exec($command,$output,$return_var);
			unlink($tmp_file);

			$output = implode("\n",$output);

			if (preg_match("#stdin: OK#",$output)){
				$result = true;
			} else {
				$result = false;
				$this->last_message = $output;
			}

			return $result;
		}

	}
//}


$rgsCertificate = new RgsCertificate(OPENSSL_PATH,RGS_VALIDCA_PATH);

$handle = fopen("php://output","w");

$sql = "SELECT users.id,certificate,givenname,users.name,users.email, users.authority_id, authorities.name as authority_name, users.authority_group_id, authority_groups.name as group_name FROM users ".
		" LEFT JOIN authorities ON users.authority_id = authorities.id ".
		" LEFT JOIN authority_groups ON users.authority_group_id=authority_groups.id ".
		" ORDER BY users.id";

$sql_actes = "SELECT max(actes_transactions_workflow.date) FROM actes_transactions " .
		" JOIN actes_transactions_workflow ON actes_transactions.id=actes_transactions_workflow.transaction_id " .
		" WHERE actes_transactions.user_id=? AND actes_transactions.type = 1";

$sql_helios = "SELECT max(helios_transactions_workflow.date) FROM helios_transactions " .
	" JOIN helios_transactions_workflow ON helios_transactions.id=helios_transactions_workflow.transaction_id " .
	" WHERE helios_transactions.user_id=?";


foreach($sqlQuery->query($sql) as $line){

	if (! $rgsCertificate->isRgsCertificate($line['certificate'])){
		unset($line['certificate']);
		$line['last_acte'] = $sqlQuery->queryOne($sql_actes,$line['id']);
		$line['last_helios'] = $sqlQuery->queryOne($sql_helios,$line['id']);
		fputcsv($handle,$line);
	}
}

fclose($handle);

