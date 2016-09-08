<?php

require_once(SITEROOT . "/class/Trace.class.php");

class Helpers {
  public static $last_error;

  /**
   * \brief Méthode renvoyant une variable récupérée depuis une requête POST
   * \param $name chaîne : nom de la variable à récupérer
   * \param $memorize booléen (optionnel) : Détermine si la variable doit être enregistré dans la session
   * \return La valeur de la variable ou null si la variable est introuvable
  */
  public static function getVarFromPost($name, $memorize = false) {
    return Helpers::getVarFromRequest($name, "POST", $memorize);
  }

  /**
   * \brief Méthode renvoyant une variable récupérée depuis une requête GET
   * \param $name chaîne : nom de la variable à récupérer
   * \param $memorize booléen (optionnel) : Détermine si la variable doit être enregistré dans la session
   * \return La valeur de la variable ou null si la variable est introuvable
  */
  public static function getVarFromGet($name, $memorize = false) {
    return Helpers::getVarFromRequest($name, "GET", $memorize);
  }

  /**
   * \brief Méthode renvoyant une variable récupérée depuis une requête HTTP
   * \param $name chaîne : nom de la variable à récupérer
   * \param $type chaîne : type de la requête GET ou POST
   * \param $memorize booléen (optionnel) : Détermine si la variable doit être enregistré dans la session
   * \return La valeur de la variable ou null si la variable est introuvable
  */
  public static function getVarFromRequest($name, $type, $memorize = false) {
    if ($type == "POST") {
      $var = &$_POST;
    } elseif ($type == "GET") {
      $var = &$_GET;
    }

    $ret = (isset($var[$name])) ? $var[$name] : null;

	if (is_array($ret)) {
	  foreach ($ret as $key => $value) {
		$ret[$key] = Helpers::stripSlashes($value);
	  }
	} else {
	  $ret = Helpers::stripSlashes($ret);
	}

	if ($memorize) {
	  Helpers::putInSession($name, $ret);
	}

    return $ret;
  }

  /**
   * \brief Méthode de suppression des échappements dans une chaîne
   * \param $str chaîne : chaîne à traiter
   * \return La chaîne sans échappement
  */
  public static function stripSlashes($str) {
	  //Suite à la deprecation de get_magic_quotes_gpc()
    /*if (get_magic_quotes_gpc() == 1) {
	  return stripslashes($str);
	} else {
	  return $str;
	}*/
	  return $str;
  }

  /**
   * \brief Méthode renvoyant une variable présente dans la session
   * \param $name chaîne : nom de la variable à récupérer
   * \param $delete booléen (optionnel) : Détermine si la variable doit être supprimé après récupération (true par défaut)
   * \return La valeur de la variable ou null si la variable est introuvable
  */
  public static function getFromSession($name, $delete = true) {
	$ret = null;
	if (isset($_SESSION["temp"][$name])) {
	  $ret = $_SESSION["temp"][$name];
	}

	if ($delete) {
	  unset($_SESSION["temp"][$name]);
	}

	return $ret;
  }

  /**
   * \brief Méthode d'ajout d'une variable dans la session
   * \param $name chaîne : nom de la variable à enregistrer
   * \param $value chaîne : Valeur de la variable
  */
  public static function putInSession($name, $value) {
	$_SESSION["temp"][$name] = $value;
  }

  /**
   * \brief Méthode effacant les variables temporaires de la session
  */
  public static function purgeTempSession() {
	unset($_SESSION["temp"]);
  }

  /**
   * \brief Méthode de redirection et renvoi de status prenant en compte le type de client (API ou formulaire Web)
   * \param $status entier : statut global du retour (0 : succès, 1 : erreur)
  		//FIXME : n'importe quoi, c'est une inversion de true=1 et false=0!!!!
   * \param $msg chaîne : message à renvoyer
   * \param $redirect (optionnel) : URL vers laquelle rediriger
   * \param $apiMsg (optionnel) : message renvoyé dans le cas d'un appel par API (sinon $msg)
  */
  public static function returnAndExit($status, $msg, $redirect = null, $apiMsg = null) {

	  //Permet de logguer le résultat dans un fichier, nottamment utile pour Qualigraf
	  /*$message = "[{$_SERVER['REMOTE_ADDR']}]".date("Y-m-d H:i:s")." status=$status msg=$msg apiMsg=$apiMsg\n";
	  file_put_contents("/tmp/s2low-return-and-exit.log",$message,FILE_APPEND);*/


	// Détection si appel par API C ou formulaire Web (d'abord en POST puis en GET)
	$api = Helpers::getVarFromPost("api");

	if (empty($api)) {
	  $api = Helpers::getVarFromGet("api");
	}

	if ($api != null && $api == "1") {
	  if ($status == 0) {
		// Succès
		echo "OK\n";
	  } else {
		echo "KO\n";
	  }

	  if ($apiMsg) {
		echo $apiMsg . "\n";
	  } elseif (! empty($msg)) {
		echo $msg . "\n";
	  }
	} else {
	  if ($redirect) {
		$_SESSION["error"] = nl2br($msg);
		if (TESTING_ENVIRONNEMENT){
			throw new Exception("Message : $msg");
		}
		header("Location: " . $redirect);  // @codeCoverageIgnore
	  } else { // @codeCoverageIgnore
		echo $msg . "\n";
	  }
	}
	if (TESTING_ENVIRONNEMENT){
		throw new Exception($msg);
	}
	
	exit();  // @codeCoverageIgnore
  }

  /**
   * \brief Méthode de conversion d'une date au format YYYY-MM-DD vers un timestamp
   * \param $date chaîne : Date au format YYYY-MM-DD
   * \param $at_midnight booléen (optionnel) : Générer le timestamp à minuit (à midi par défaut)
   * \return Le timestamp correspondant
  */
  public static function ansiDateToTimestamp($date, $at_midnight = false) {
	$tmp = explode('-', $date);
	$year = (int) $tmp[0];
	$month = (int) $tmp[1];
	$day = (int) $tmp[2];

	if ($at_midnight) {
	  $hour = 0;
	} else {
	  $hour = 12;
	}

	return mktime($hour, 0, 0, $month, $day, $year);
  }

  /**
   * \brief Méthode traitant une variable récupérée depuis une base de données (prise en compte de l'échappement)
   * \param $var mixed : valeur récupérée depuis la base de données
   * \return La valeur de la variable après traitement
  */
  public static function getFromBDD($var) {
	  return $var;
    /*if (get_magic_quotes_runtime()) {
      return stripslashes($var);
    } else {
      return $var;
    }*/
  }

  /**
   * \brief Méthode d'échappement des guillemets double
   * \param $str chaîne : chaîne à échappée
   * \return La chaîne avec tous les guillemets doubles précédés d'un \
  */
  public static function escapeForXML($str) {
	return str_replace("\"", "\\\"", $str);
  }

  /**
   * \brief Méthode de récupération d'un champ depuis un objet SimpleXMLElement
   * \param $elt SimpleXMLElement : objet dont extraire la valeur
   * \return La chaîne correspondante en ISO-8859-1
  */
  public static function getFromXMLElt($elt) {
	return utf8_decode(sprintf("%s", $elt));
  }

  /**
   * \brief Méthode de troncature d'une chaine à une longueur donnée
   * \param $str chaîne : chaîne à tronquer
   * \param $length entier (optionnel) : longueur résiduelle de la chaîne (40 par défaut)
   * \param $add_ellipsis booléen (optionnel) : ajouter une ellipse à la fin de la chaîne (true par défaut)
   * \return La chaîne avec tous les guillemets doubles précédés d'un \
  */
  public static function truncateString($str, $length = 40, $add_ellipsis = true) {
	$new_str = substr($str, 0, $length);

	if ($add_ellipsis && strlen($new_str) < strlen($str)) {
	  $new_str .= "...";
	}

	return $new_str;
  }

  /**
   * \brief Méthode qui renvoit une heure bien formatée depuis un format HH:MM:SS
   * \param $hour chaîne : chaîne d'heure sous la forme HH:MM:SS
   * \return La chaine bien formatée HHh MMmin SSs
  */
  public static function getPrettyHours($hour) {
	$hours = explode(':', $hour);

	if (count($hours) != 3) {
	  return null;
	}

	return $hours[0] . "h " . $hours[1] . "min " . $hours[2] . "s";
  }

  /**
   * \brief Méthode qui renvoit un timestamp correspondant à une date issue de la base de données
   * \param $date chaîne : chaîne de date issue de la base de données (YYYY-MM-DD HH:MM:SS+TZ)
   * \return Le timestamp correspondant
  */
  public static function getTimestampFromBDDDate($date) {
	if (preg_match("/^([0-9]{4})-([0-9]{2})-([0-9]{2})\s+([0-9]{2}):([0-9]{2}):([0-9]{2}).*$/", $date, $matches)) {
	  $year = $matches[1];
	  $month = $matches[2];
	  $day = $matches[3];
	  $hour = $matches[4];
	  $min = $matches[5];
	  $sec = $matches[6];

	  return mktime($hour, $min, $sec, $month, $day, $year);
	}

	return null;
  }

  /**
   * \brief Méthode qui renvoit une date formatée issue de la base de données
   * \param $date chaîne : chaîne de date issue de la base de données (YYYY-MM-DD HH:MM:SS+TZ)
   * \param $with_hours booleén (optionnel) : Si true, la chaîne incluera les heures (false par défaut)
   * \return La chaîne correspondant à la date
  */
  public static function getDateFromBDDDate($date, $with_hours = false) {
	if ($timestamp = Helpers::getTimestampFromBDDDate($date)) {

	  $str = utf8_decode( strftime("%e %B %Y", $timestamp) );

	  if ($with_hours) {
		$str .= ' à ' . utf8_decode(strftime('%Hh%Mmin%Ss', $timestamp));
	  }

	  return $str;
	}

	return null;
  }

  /**
   * \brief Méthode qui renvoit une date ANSI issue de la base de données
   * \param $date chaîne : chaîne de date issue de la base de données (YYYY-MM-DD HH:MM:SS+TZ)
   * \return La chaîne correspondant à la date au format YYYY-MM-DD
  */
  public static function getANSIDateFromBDDDate($date) {
	if ($timestamp = Helpers::getTimestampFromBDDDate($date)) {
	  $str = date("Y-m-d", $timestamp);

	  return $str;
	}

	return null;
  }


  /**
   * \brief Méthode de construction d'une URL avec un paramètre spécifié en préservant les paramètres existant
   * \param $params tableau : tableau ayant pour clef les noms des paramètres à ajouter dans l'URL et pour valeur les valeurs des paramètres
   * \return L'URL générée
   */
  public static function getURLWithParam($params) {
	$args = $_SERVER["QUERY_STRING"];

	foreach ($params as $param => $value) {
	  // Suppression du paramètre s'il existe déjà dans l'URL
	  $args = preg_replace("/&?" . $param . "=[^&]+/", "", $args);
	  // Suppression d'un éventuel & résiduel au début de la chaîne
	  $args = preg_replace("/^&/", "", $args);
	  // Détermination du séparateur pour ajouter notre paramètre
	  $sep = (strlen($args) > 0) ? "&" : "";

	  $args .= $sep . $param . "=" . $value;
	}

	// Remplacement des & par &amp; (XHTML)
	$args = preg_replace("/&/", "&amp;", $args);

	$url = WEBSITE_SSL . $_SERVER["PHP_SELF"] . "?" . $args;

	return $url;
  }

  /**
   * \brief Méthode de création d'une arborescence de répertoire (sous ACTES_FILES_UPLOAD_ROOT par défaut)
   * \param $path chaîne : chemin absolu vers l'arborescence à créer
   * \param $base chaîne (optionnel) : répertoire de base de la création (ACTES_FILES_UPLOAD_ROOT par défaut)
   * \return True en cas de succès, false sinon
   */
  public static function createDirTree($path, $base = ACTES_FILES_UPLOAD_ROOT) {
  	$escBase = str_replace("/", '\/', $base);
	if (preg_match('/^' . $escBase . '\/*/', $path)) {
	  $relPath = preg_replace('/^' . $escBase . '\\/*/', "", $path);
	} else {
		$t = Trace::getInstance();
		$t->log("Impossible de créer le répertoire (unknow reason): $path ",Trace::$TRACE_ERROR);
	  return false;
	}

	if (! file_exists($path)) {
	  if (! mkdir($path, GENERATED_DIRS_PERMS, true)) {
	  	$t = Trace::getInstance();
	  	$t->log("Impossible de créer le répertoire (mkdir failed): $path ",Trace::$TRACE_ERROR);
		return false;
	  }

	  // Modification des permissions de toute l'arborescence créée
	  while (strlen($relPath) > 0) {
		Helpers::fixPerms($base . "/" . $relPath);
		$relPath = preg_replace('/[^\/]+\/*$/', "", $relPath);
	  }
	} elseif (! is_dir($path)) {
		$t = Trace::getInstance();
		$t->log("Impossible de créer le répertoire (file exists): $path ",Trace::$TRACE_ERROR);
	  return false;
	} else {
	  return true;
	}

	return true;
  }

  /**
   * \brief Méthode de suppression de fichiers et répertoires
   * \param .. Liste variables de fichiers et répertoires à supprimer
   * \return True en cas de succès, false sinon
   */
  public static function deleteFromFS() {
	$return_value = true;

	for ($i = 0; $i < func_num_args(); $i++) {
	  $entry = func_get_arg($i);
	  if (file_exists($entry)) {
		if (is_dir($entry)) {
		  if (! @rmdir($entry)) {
			$return_value = false;
		  }
		} elseif (is_file($entry) || is_link($entry)) {
		  if (! @unlink($entry)) {
			$return_value = false;
		  }
		}
	  }
	}

	return $return_value;
  }

  /**
   * \brief Méthode de modification des permissions en fonction de la configuration actuelle
   * \param $path chaîne : chemin vers le fichier ou répertoire dont modifier les permissions
   * \return True en cas de succès, false sinon
   */
  public static function fixPerms($path) {
  	
  	$t = Trace::getInstance();
	$t->log("Modification des droits de : $path ",Trace::$TRACE_DEBUG);
	
	if (file_exists($path)) {
	  if (is_dir($path)) {
		$r =  chmod($path, GENERATED_DIRS_PERMS);
		if (! $r){
			$t->log("Echec de l'attribution des droits : $path ",Trace::$TRACE_ERROR);
		}
		return $r; 
	  } elseif (is_file($path)) {
		return chmod($path, GENERATED_FILES_PERMS);
	  }
	}

	return false;
  }

  /**
   * \brief Méthode de récupération des informations des certificats reconnues par le système
   * \param $path chaîne (optionnel) : chemin vers le répertoire contenant les certificats (defaut EXTENDED_VALIDCA_PATH)
   * \return Un tableau des données des certificats
   */
  public static function getAuthorizedCACerts($path = EXTENDED_VALIDCA_PATH) {
	$certs = array();

	if (is_dir($path)) {
	  if (! $files = scandir($path)) {
		return false;
	  }

	  foreach ($files as $file) {
		$file = $path . "/" . $file;

		if (is_file($file) && ! is_link($file)) {
		  if (! $cert = @file_get_contents($file)) {
		  	
 	  		Helpers::$last_error.= "Certficate file error in validca:".$file."\n";
			continue;
		  }

		  if ($x509 = openssl_x509_parse($cert)) {
			//print_r($x509);
			$certs[] = $x509;
		  }
		}
	  }
	}

	return $certs;
  }

  /**
   * \brief Méthode d'envoi d'un fichier au navigateur
   * \param $path chaîne : chemin vers le fichier à envoyer, si null envoi des en-têtes uniquement
   * \param $filename chaîne : nom du fichier dans le navigateur
   * \param $content_type chaîne (optionnel) : content-type du fichier
   * \return True en cas de succès, false sinon
   */
  public static function sendFileToBrowser($path, $filename, $content_type = null) {
	if ($path) {
	  if (! file_exists($path)) {
		Helpers::$last_error = "Fichier spécifié introuvable";
		return false;
	  }
	}
	
	if ($content_type) {
	  header("Content-type: " . $content_type);
	}

	header('Content-disposition: attachment; filename="' . $filename . '"');
	// Celles-ci pour IE
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
	header("Pragma: public");

	if ($path) {
	  if (! @readfile($path)) {
		Helpers::$last_error = "Erreur lors de la lecture du fichier";
		return false;
	  }
	}

	return true;
  }

  /**
   * \brief Méthode de génération d'un nom temporaire
   * \param $length entier (optionnel) : longueur du suffixe aléatoire
   * \param $prefix booleén (optionnel) : ajouter ou non le préfixe "__tmp__" devant la chaine générée (défaut true)
  */
  public static function genTempName($length = 8, $prefix = true) {
	if ($prefix) {
	  $tmp = "__tmp__";
	} else {
	  $tmp = "";
	}

	for ($i = 0; $i < $length; $i++) {
	  $tmp .= rand(1,9);
	}

	return $tmp;
  }

  /**
   * \brief Méthode de récupération du type d'un fichier
   * \param $path chaine : chemin vers le fichier
   * \return Le type MIME du fichier ou null en cas d'erreur
  */
  public static function getFileType($path) {
	if (file_exists($path)) {
	  return mime_content_type($path);
	} else {
	  return null;
	}
  }

	public function chunkString($string,$length){
		$result = substr($string, 0, $length);
		if (strlen($string) > 40) {
			$result .= "...";
		}
		return $result;
	}

}
