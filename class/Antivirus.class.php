<?php
class Antivirus {
	public $errorMsg;

    /**
     * @param $path
     * @return bool
     * @throws Exception
     */
 	public function checkArchiveSanity($path) {
		$new_file = ANTIVIRUS_TMP_PATH . basename($path);		
		
		$new_file = escapeshellarg($new_file);
		$path = escapeshellarg($path);
		
		Trace::wrap_exec("cp $path $new_file",$output, $ret);
		
		if ( $ret != 0 ){
			$t = Trace::getInstance();
			$t->log("Impossible de copier $path vers $new_file",Trace::$TRACE_ERROR);
			$this->errorMsg="Impossible de copier $path vers $new_file";
			return false;
		}

		Trace::wrap_exec("chmod 644 $new_file",$output, $ret);
	 	
		Trace::wrap_exec(ANTIVIRUS_COMMAND . " $new_file", $output, $ret);
		Trace::wrap_exec("rm $new_file",$output1,$ret1);
		
		switch ($ret) {
		  case 0:
				$returnValue= true;
				break;
		  case 1:
			  $this->errorMsg = "L'archive est infectée par un virus. Retour de l'antivirus&nbsp;:<br />\n";
				// Format de ligne : /Nom/de/fichier: Nom virus
				foreach ($output as $line) {
				  if (preg_match('/^\/.*: .* FOUND$/', $line)) {
					$line = explode(":", $line);
					  $this->errorMsg .= basename($line[0]) . " : " . $line[1] . "<br />\n";
				  }
				}
				$returnValue= false;
				break;
		  default:
			  $this->errorMsg = "Erreur " . $ret . " lors du scan antivirus de l'archive.";
				throw new Exception("Erreur " . $ret . " lors du scan antivirus de l'archive.");

				break;
	  }
	  return $returnValue;
 	}

	/**
	 * @return bool
	 * @throws Exception
	 */
	public function isAlive(){
		Trace::wrap_exec(ANTIVIRUS_COMMAND . " " . __FILE__ ." 2>&1", $output, $ret);

		if ($ret == 0){
			return true;
		}

		throw new Exception("Problème avec l'antivirus : " . implode("\n",$output));
	}


}