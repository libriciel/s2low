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

    /**
     * @param $remote_path
     * @param $local_path
     * @throws Exception
     */
	public function recupAll($remote_path,$local_path){
        $sigtermHandler = new SigTermHandler();
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

		//Attention, sur un serveur normal, c'est . par contre sur le site de la DGFip , c'est ./
		if (HELIOS_SENDING_MODE_DEMO) {
			$all_file = ftp_nlist($ftp, ".");
		} else {
			$all_file = ftp_nlist($ftp, "./");
		}

		echo "Il y a ".count($all_file)." fichiers en attente...\n";
		
		if ($all_file === false){
			throw new Exception("Impossible de lister le contenu du répertoire distant $remote_path");
		}
		
		foreach($all_file as $i => $file){
			if(preg_match("#^PESALR2_#",basename($file))){
				echo "$i : $file : PES ALLER ignoré\n";
				continue;
			}

            $tmp_file = sys_get_temp_dir()."/s2low_helios_ftp_retrieve_".mt_rand(0,mt_getrandmax());

			if (disk_free_space($local_path) < 1000000 || disk_free_space(dirname($tmp_file)) < 1000000){
				throw new Exception("Il ne reste pas assez d'espace sur le disque !");
			}


            $ftp_get_result = ftp_get($ftp, $tmp_file, "$file", FTP_ASCII);

            if (!$ftp_get_result){
             	throw new Exception("Impossible de récupérer le fichier $file pour le mettre sur $tmp_file sur le FTP {$this->host}");
            }

			$rename_result = rename($tmp_file,"$local_path/$file");
			if (!$rename_result){
				throw new Exception("Impossible de déplacer le fichier $tmp_file vers $local_path/$file");
			}

			echo $i." : ".$file . " récupéré : ".($ftp_get_result?"SUCCES":"ECHEC")."\n";
			if ($this->delete){
				ftp_delete($ftp, $file);
			}
            if ($sigtermHandler->isSigtermCalled()){
                ftp_close($ftp);
                break;
            }
		}
		
		ftp_close($ftp);
	}
	
	
}