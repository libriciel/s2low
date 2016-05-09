<?php
class FTP {
	
	private $host;
	private $port;
	private $login;
	private $password;
	private $delete;
	
	public function setConnexionInfo($host,$port,$login,$password){
		$this->host = $host;
		$this->port = $port;
		$this->login = $login;
		$this->password = $password;
	}
	
	public function setDeleteFileAfterDownload(){
		$this->delete = true;
	}
	
	public function recupAll($remote_path,$local_path){
		$ftp = ftp_connect($this->host,$this->port);
		
		if (!$ftp){
			throw new Exception("Impossible de se connecter au serveur {$this->host}:{$this->port}");
		}
		
		if ($this->login){
			if (! ftp_login($ftp,$this->login,$this->password)){
				throw new Exception("Impossible de se connecter avec le login {$this->login}");
			}
		}
		
		if ( ! ftp_chdir($ftp,$remote_path)){
			throw new Exception("Impossible d'aller sur le répertoire distant $remote_path");
		}
		
		$all_file = ftp_nlist($ftp,"./");

		echo "Il y a ".count($all_file)." fichiers en attente...\n";
		
		if ($all_file === false){
			throw new Exception("Impossible de lister le contenu du répertoire distant $remote_path");
		}
		
		foreach($all_file as $i => $file){
			if(preg_match("#^PESALR2_#",basename($file))){
				echo "$i : $file : PES ALLER ignoré\n";
				continue;
			}
			$err = ftp_get($ftp, "$local_path/$file", "$file", FTP_ASCII);
			echo $i." : ".$file . " récupéré : ".($err?"SUCCES":"ECHEC")."\n";
			if ($err && $this->delete){
				ftp_delete($ftp, $file);
			}
		}
		
		ftp_close($ftp);
	}
	
	
}