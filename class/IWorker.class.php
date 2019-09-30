<?php

interface IWorker {

	/**
	 * Le nom de la queue pour Beanstalkd
	 * @return string
	 */
	public function getQueueName();

	/**
	 * En fonction d'un identifiant, retourne les données à envoyé sur la queue
	 * Cela permettrait d'optimiser pour ne pas avoir a faire des requêtes lors du travail, mais charge la queue...
	 * @param $id int identifiant
	 * @return mixed donnée à envoyé sur la queue
	 */
	public function getData($id);

	/**
	 * Renvoie une liste d'identifiant pour reconstruire une file
	 * @return int[]
	 */
	public function getAllId();

	/**
	 * Le vrai travail avec les data
	 * @throws RecoverableException
	 * @param $data
	 * @return bool
	 */
	public function work($data);


	/**
	 * @param $data
	 * @return string|false le nom du verrou exlusif à utiliser pour la section critique "work", false si work n'est pas une section critique
	 */
	public function getMutexName($data);

	/**
	 * @param $data
	 * @return boolean indique si les données sont encore valide (i.e la transaction dans le bon état par exemple), si false, on sort le travail de la file
	 */
	public function isDataValid($data);


}