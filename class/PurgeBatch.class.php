<?php

class PurgeBatch {

    public function __construct() {
        if (PHP_SAPI != 'cli') {
            header("Content-type: text/html; charset=iso-8859-15");
        }
    }

    public function trace($texte, $utf8 = true, $toFile = false) {
        if ($toFile || ($utf8 && (PHP_SAPI == 'cli'))) {
            $texte = utf8_encode($texte);
        }
        if ($toFile) {
            throwIfFalse(file_put_contents($toFile, $texte, FILE_APPEND));
        } else {
            echo $texte;
        }
    }

    public function traceln($texte = '', $utf8 = true, $toFile = false) {
        $this->trace($texte . (PHP_SAPI == 'cli' ? "\n" : "<BR/>"), $utf8, $toFile);
    }

    public function heure() {
        return '[' . date('d/m/Y H:i:s') . ']';
    }

    public function isBatchStop() {
        $files = glob('/tmp/batch.stop');
        return $files;
    }

    public function displayBatchStopAndDie() {
        $this->traceln($this->heure() . " Script batch interrompu par fichier flag.");
        die(1);
    }

    public function checkBatchStop() {
        if ($this->isBatchStop()) {
            $this->displayBatchStopAndDie();
        }
    }

    public function read($prompt, $default = null) {
        echo utf8_encode($prompt . ' : ');
        if (!defined('STDIN')) {
            $this->error('Saisie impossible, pas de flux d\'entrÃ©e.');
        }
        $ret = utf8_decode(trim(fgets(STDIN)));
        if (empty($ret)) {
            if (isset($default)) {
                return $default;
            }
            $this->error('Abandon');
            exit(1);
        }
        return $ret;
    }

    private function error($text) {
        $this->traceln($text);
        exit(1);
    }

    /**
     * En mode http comme en mode 'cli', les paramÃ¨tres sont fournis au format {name}={value}.<br>
     * L'argument est obligatoire si $default est null ou STDIN.<br>
     * La valeur peut Ãªtre saisie, en mode CLI, si elle n'est pas fournie et
     * que $defaut est STDIN.<br>
     * @param string $name
     * @param mixed $default la valeur de l'argument si l'argument n'est pas dÃ©clarÃ©.<br>
     *      STDIN pour
     * @return mixed
     */
    public function getArg($name, $default = null) {
        global $argc;
        global $argv;

        if (PHP_SAPI == 'cli') {
            for ($iarg = 1; $iarg < count($argv); $iarg++) {
                $argNameValue = explode('=', $argv[$iarg]);
                $argName = $argNameValue[0];
                if ($argName == $name) {
                    if (count($argNameValue) >= 2) {
                        $argValue = $argNameValue[1];
                    } else {
                        $argValue = null;
                    }
                    break;
                }
            }
        } else {
            $argValue = isset($_GET[$name]) ? $_GET[$name] : null;
        }

        if (!empty($argValue)) {
            return $argValue;
        }
        if (!isset($default)) {
            throw new Exception('ParamÃ¨tre \'' . $name . '\' non fourni');
        }
        if (defined('STDIN') && ($default === STDIN)) {
            $argValue = $this->read($name);
            return $argValue;
        }
        return $default;
    }

    /**
     * ExÃ©cute une fonction en mesurant sa durÃ©e et la mÃ©moire consommÃ©e.
     * @param Closure $function fonction exÃ©cutant le traitement Ã  mesurer. Aucun paramÃ¨tre.
     * @return array tableau Ã  indexation numÃ©rique contenant le rÃ©sultat de la fonction, la durÃ©e, la mÃ©moire consommÃ©e
     */
    function mesurer($function) {
        $debut = microtime(true);
        $mem = memory_get_usage(true);
        $result = $function();
        $duree = round(microtime(true) - $debut, 3);
        $mem = memory_get_usage(true) - $mem;
        return array($result, $duree, $mem);
    }

    /**
     * @return PDOStatement
     */
    function sqlPrepare(SQLQuery $sqlQuery, $sql) {
        /** @var $pdo PDO */
        $pdo = $this->invokePrivate($sqlQuery, 'getPdo');
        $stmt = $pdo->prepare($sql);
        $stmt->pdo = $pdo;
        return $stmt;
    }

    /**
     * ExÃ©cute une requÃªte INSERT et renvoie l'id du dernier Ã©lÃ©ment ajoutÃ©.
     * @return int id du dernier Ã©lÃ©ment ajoutÃ©, ou FALSE en cas d'erreur (attention Ã  la diffÃ©rence entre FALSE et 0)
     */
    function sqlInsert(PDOStatement $stmt, array $params = null) {
        /** @var $pdo PDO */
        $pdo = $stmt->pdo;
        $result = $stmt->execute($params);
        $lastInsertId = $result ? $pdo->lastInsertId() : FALSE;
        $stmt->closeCursor();
        return $lastInsertId;
    }

    /**
     * ExÃ©cute une requÃªte UPDATE et renvoie le nombre de lignes modifiÃ©es.
     * @return mixed nombre de lignes modifiÃ©es, ou FALSE en cas d'erreur (attention Ã  la diffÃ©rence entre FALSE et 0)
     */
    function sqlUpdate(PDOStatement $stmt, array $params = null) {
        $result = $stmt->execute($params);
        $rowCount = $result ? $stmt->rowCount() : FALSE;
        $stmt->closeCursor();
        return $rowCount;
    }

    /**
     * ExÃ©cute une requÃªte DELETE et renvoie le nombre de lignes supprimÃ©es.
     * @return mixed nombre de lignes supprimÃ©es, ou FALSE en cas d'erreur (attention Ã  la diffÃ©rence entre FALSE et 0)
     */
    function sqlDelete(PDOStatement $stmt, array $params = null) {
        $result = $stmt->execute($params);
        $rowCount = $result ? $stmt->rowCount() : FALSE;
        $stmt->closeCursor();
        return $rowCount;
    }

    /**
     * ExÃ©cute une requÃªte SELECT et renvoie tous les Ã©lÃ©ments
     * @return array Ã©lÃ©ments, ou FALSE en cas d'erreur
     */
    function sqlSelect(PDOStatement $stmt, array $params = null) {
        $result = $stmt->execute($params);
        if (!$result) {
            return FALSE;
        }
        $result = $stmt->fetchAll();
        $stmt->closeCursor();
        return $result;
    }

    /**
     * ExÃ©cute une requÃªte SELECT et renvoie une seule ligne
     * @return array Ã©lÃ©ments, ou FALSE en cas d'erreur
     */
    function sqlSelectOne(PDOStatement $stmt, array $params = null) {
        $result = $stmt->execute($params);
        if (!$result) {
            return FALSE;
        }
        $result = $stmt->fetch();
        $stmt->closeCursor();
        return $result;
    }

    /**
     * @return SimpleXMLElement
     */
    public function loadXml($file_path) {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_file($file_path, 'SimpleXMLElement', LIBXML_PARSEHUGE);
        if ($xml === false) {
            $errors = '';
            foreach (libxml_get_errors() as $error) {
                $errors .= $error->message . '(Lig' . $error->line . ',Col' . $error->column . ')';
            }
            throw new Exception("Le fichier $file_path n'est pas un XML correct : " . $errors);
        }
        return $xml;
    }

    public function throwIfFalse($result, $message = false) {
        if ($result === false) {
            $this->throwLastError($message);
        }
        return $result;
    }

    public function throwLastError($message = false) {
        $last = error_get_last();
        $cause = $last['message'];
        if ($message) {
            $ex = $message . ' Cause : ' . $cause;
        } else {
            $ex = $cause;
        }
        throw new Exception($ex);
    }

    /**
     * ExÃ©cuter une mÃ©thode d'un objet, mÃªme si elle est private.
     * @param object $object
     * @param name $methodName
     * @param array $parameters
     * @return mixed
     */
    public function invokePrivate(&$object, $methodName, array $parameters = array()) {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    public function htmlTableCell($value, $align = 'left', $balise = 'td') {
        return '<' . $balise . ' align="' . $align . '">' . $value . '</' . $balise . '>';
    }

    public function htmlhyperlink($link, $value) {
        return '<a href="' . $link . '">' . $value . '</a>';
    }

    public function htmlvalue($value) {
        return htmlentities($value, ENT_COMPAT | ENT_HTML401, 'ISO-8859-1');
    }
}
