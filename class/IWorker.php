<?php

namespace S2lowLegacy\Class;

use S2lowLegacy\Lib\PausingQueueException;
use S2lowLegacy\Lib\UnrecoverableException;

interface IWorker
{
    /**
     * Le nom de la queue pour Beanstalkd
     * @return string
     */
    public function getQueueName(): string;

    /**
     * En fonction d'un identifiant, retourne les données à envoyé sur la queue
     * Cela permettrait d'optimiser pour ne pas avoir a faire des requêtes lors du travail, mais charge la queue...
     * @param $id int identifiant
     * @return mixed donnée à envoyé sur la queue
     */
    public function getData($id): mixed;

    /**
     * Renvoie une liste d'identifiant pour reconstruire une file
     * @return int[]
     */
    public function getAllId(): array;

    /**
     * Le vrai travail avec les data
     * @throws RecoverableException | CloudStorageException | PausingQueueException | UnrecoverableException
     * @param $data
     * @return bool
     */
    public function work($data);


    /**
     * @param $data
     */
    public function getMutexName($data): string;

    /**
     * @param $data
     * @return boolean indique si les données sont encore valide (i.e la transaction dans le bon état par exemple), si false, on sort le travail de la file
     */
    public function isDataValid($data): bool;

    /**
     * @return void
     */
    public function start(): void;

    /**
     * @return void
     */
    public function end(): void;
}
