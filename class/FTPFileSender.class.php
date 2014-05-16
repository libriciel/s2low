<?php
class FTPFileSender {
	
	
	private $ftp_handler;
	
	
	public function connect($host,$port,$login,$password){
		$this->ftp_handler = ftp_connect($host,$port);
		
		if (!$this->ftp_handler){
			throw new Exception("Impossible de se connecter au serveur {$host}:{$port}");
		}
		
		if ($login){
			if (! ftp_login($this->ftp_handler,$login,$password)){
				throw new Exception("Impossible de se connecter avec le login {$login}");
			}
		}
	}
	
	public function sendRawCommand($command,$mode_demo=false){
		$result = ftp_raw($this->ftp_handler, $command);
		if ($mode_demo){
			return ;
		}
		if (!$result || ! preg_match("#^quote#",$result[0])){
			$message =  "[FAILED] Send FTP raw command\n$command\n********** RESULT *******\n";
			$message .= implode("\n",$result)."\n";
			$message .=  "******** END RESULT ************\n";
			throw new Exception($message);
		}
	}
	
	public function sendFile($directory_destination,$file_path){
		$result = ftp_put($this->ftp_handler,$directory_destination.basename($file_path),$file_path,FTP_BINARY);
		if (! $result){
			throw new Exception("Erreur lors de l'envoi du fichier ".basename($file_path) ." vers le serveur FTP");
		}
	}
	
	public function disconnect(){
		ftp_close($this->ftp_handler);
	}
	
	
}