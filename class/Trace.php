<?php

namespace S2lowLegacy\Class;

/**
 * \class Trace Trace.class.php
 * \brief Gestion des journaux d'enregistrement
 * \author Eric Pommateau
 * \date 13.03.2007
 *
 */

class Trace
{
    public static $TRACE_DEBUG = 'debug';

    public static $TRACE_INFO = 'info';

    public static $TRACE_ERROR = 'error' ;

  /**
   * \brief Permet de récupérer une instance de la classe Trace
   */
    public static function getInstance()
    {
        if (!self::$trace) {
            self::$trace = new Trace();
        }
        return self::$trace;
    }

    /**
     * \brief Permet de logguer un $message dans le niveau $level
     * \param message string le message a enregistrer
     * \param level string le niveau de log
     */
    public function log($message, $level = null)
    {
        if ($level == null) {
            $level = Trace::$TRACE_INFO;
        }
        $date = date("Y-m-d H:i:s");
        if (! $this->fichier) {
            $this->open();
        }
        if (! $this->fichier) {
            echo "impossible d'ouvrir le fichier de log : " . TRACE_FILE_PATH ;
            return;
        }
        fwrite($this->fichier, "[$date] [$level] - $message\n");
    }

    private static $trace;

    private $fichier;

    public function __destruct()
    {
        if ($this->fichier) {
            fclose($this->fichier);
        }
    }

    private function open()
    {
        $this->fichier = fopen(TRACE_FILE_PATH, "a");
    }
}
