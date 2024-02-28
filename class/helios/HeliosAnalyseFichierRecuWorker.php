<?php

namespace S2lowLegacy\Class\helios;

use S2lowLegacy\Class\IWorker;
use Exception;

class HeliosAnalyseFichierRecuWorker implements IWorker
{
    public const QUEUE_NAME = 'helios-analyse-fichier-recu';


    private $heliosAnalyseFichierRecu;

    public function __construct(
        HeliosAnalyseFichierRecu $heliosAnalyseFichierRecu
    ) {
        $this->heliosAnalyseFichierRecu = $heliosAnalyseFichierRecu;
    }

    public function getQueueName()
    {
        return self::QUEUE_NAME;
    }

    public function getData($id)
    {
        return $id;
    }

    /**
     * @return array|false|int[]
     * @throws Exception
     */
    public function getAllId()
    {
        return $this->heliosAnalyseFichierRecu->getAllDirectory(HELIOS_FTP_RESPONSE_TMP_LOCAL_PATH);
    }

    /**
     * @param $data
     * @return void
     * @throws Exception
     */
    public function work($data)
    {
        $this->heliosAnalyseFichierRecu->analyseOneFileForWorker(
            HELIOS_FTP_RESPONSE_TMP_LOCAL_PATH,
            HELIOS_RESPONSES_ROOT,
            HELIOS_RESPONSES_ERROR_PATH,
            HELIOS_OCRE_FILE_PATH,
            $data
        );
    }

    public function getMutexName($data)
    {
        return sprintf("%s-%s", self::QUEUE_NAME, $data);
    }

    public function isDataValid($data)
    {
        return true;
    }

    /**
     * @return void
     */
    public function start()
    {
        // TODO: Implement start() method.
    }

    /**
     * @return void
     */
    public function end()
    {
        // TODO: Implement end() method.
    }
}
