<?php
class Antivirus
{
	public static $errorMsg;

 	public static function checkArchiveSanity($path) {

		//FIXME
		if(TESTING_ENVIRONNEMENT){
			return true;
		}

		$new_file = ANTIVIRUS_TMP_PATH . basename($path);		
		
		$new_file = escapeshellarg($new_file);
		$path = escapeshellarg($path);
		
		Trace::wrap_exec("cp $path $new_file",$output, $ret);
		
		if ( $ret != 0 ){
			$t = Trace::getInstance();
			$t->log("Impossible de copier $path vers $new_file",Trace::$TRACE_ERROR);
			Antivirus::$errorMsg="Impossible de copier $path vers $new_file";
			return false;
		}

		Trace::wrap_exec("chmod 644 $new_file",$output, $ret);
	 	
		Trace::wrap_exec(ACTES_ANTIVIRUS_COMMAND . " $new_file", $output, $ret);

	  switch ($ret) {
		  case 0:
				$returnValue= true;
				break;
		  case 1:
				Antivirus::$errorMsg = "L'archive est infectée par un virus. Retour de l'antivirus&nbsp;:<br />\n";
				// Format de ligne : /Nom/de/fichier: Nom virus
				foreach ($output as $line) {
				  if (preg_match('/^\/.*: .* FOUND$/', $line)) {
					$line = explode(":", $line);
					Antivirus::$errorMsg .= basename($line[0]) . " : " . $line[1] . "<br />\n";
				  }
				}
				$returnValue= false;
				break;
		  default:
				Antivirus::$errorMsg = "Erreur " . $ret . " lors du scan antivirus de l'archive.";
				$returnValue= false;
				break;
	  }
	  Trace::wrap_exec("rm $new_file",$output, $ret);
	  return $returnValue;
 	}

	public static function isAlive(){
		Trace::wrap_exec(ACTES_ANTIVIRUS_COMMAND . " " . __FILE__ ." 2>&1", $output, $ret);

		if ($ret == 0){
			return true;
		}

		throw new Exception("Problème avec l'antivirus : " . implode("\n",$output));
	}


}