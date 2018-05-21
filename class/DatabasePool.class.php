<?php

/**
 * Class DatabasePool
 */
class DatabasePool {
	/**
	 * retourne une référence vers un objet de type Database correspondant
	 * aux paramètres de connexion spécifiés lors de l'appel
	 *
	 * @param string $host string le nom du serveur de base de données
	 * @param string $user string nom d'utilisateur pour la connexion à la base
	 * @param string $password mot de passe pour la connexion à la base
	 * @param string $base nom de la base de données à laquelle se connecter
	 * @return Database une référence vers un objet de type Database permettant de faire des requêtes sur la base choisie
	 *
	 */
	public static function getInstance($host=DB_HOST, $user=DB_USER, $password=DB_PASSWORD, $base=DB_DATABASE) {

		//Putain de singleton utilisé n'importe où...
		//On est donc contraint de regarder si on est en test pour ce brancher sur la base de test
		if (TESTING_ENVIRONNEMENT){
			$host = DB_HOST_TEST;
			$user = DB_USER_TEST;
			$password = DB_PASSWORD_TEST;
			$base = DB_DATABASE_TEST;
		}


		static $pool = array();

		// On cherche si l'on a une instance correspondant aux paramètres spécifiés
		$found = false;
		while (list($key, $conn) = each($pool) && ! $found) {
			if ($conn && $conn->host == $host && $conn->user == $user && $conn->password == $password && $conn->base == $base) {
				$found = true;
				$goodKey = $key;
			}
		}

		// On n'a pas trouvé d'instance => soit le pool est vide soit il ne contient pas la connexion désirée
		if (! $found) {
			$goodKey = count($pool);
			$pool[$goodKey] = new Database($host, $user, $password, $base);
		}

		return $pool[$goodKey];
	}
}