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
            $this->error('Saisie impossible, pas de flux d\'entrée.');
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
     * En mode http comme en mode 'cli', les paramètres sont fournis au format {name}={value}.<br>
     * L'argument est obligatoire si $default est null ou STDIN.<br>
     * La valeur peut être saisie, en mode CLI, si elle n'est pas fournie et
     * que $defaut est STDIN.<br>
     * @param string $name
     * @param mixed $default la valeur de l'argument si l'argument n'est pas déclaré.<br>
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
            throw new Exception('Paramètre \'' . $name . '\' non fourni');
        }
        if (defined('STDIN') && ($default === STDIN)) {
            $argValue = $this->read($name);
            return $argValue;
        }
        return $default;
    }

    /**
     * Exécute une fonction en mesurant sa durée et la mémoire consommée.
     * @param Closure $function fonction exécutant le traitement à mesurer. Aucun paramètre.
     * @return array tableau à indexation numérique contenant le résultat de la fonction, la durée, la mémoire consommée
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
     * Exécute une requête INSERT et renvoie l'id du dernier élément ajouté.
     * @return int id du dernier élément ajouté, ou FALSE en cas d'erreur (attention à la différence entre FALSE et 0)
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
     * Exécute une requête UPDATE et renvoie le nombre de lignes modifiées.
     * @return mixed nombre de lignes modifiées, ou FALSE en cas d'erreur (attention à la différence entre FALSE et 0)
     */
    function sqlUpdate(PDOStatement $stmt, array $params = null) {
        $result = $stmt->execute($params);
        $rowCount = $result ? $stmt->rowCount() : FALSE;
        $stmt->closeCursor();
        return $rowCount;
    }

    /**
     * Exécute une requête DELETE et renvoie le nombre de lignes supprimées.
     * @return mixed nombre de lignes supprimées, ou FALSE en cas d'erreur (attention à la différence entre FALSE et 0)
     */
    function sqlDelete(PDOStatement $stmt, array $params = null) {
        $result = $stmt->execute($params);
        $rowCount = $result ? $stmt->rowCount() : FALSE;
        $stmt->closeCursor();
        return $rowCount;
    }

    /**
     * Exécute une requête SELECT et renvoie tous les éléments
     * @return array éléments, ou FALSE en cas d'erreur
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
     * Exécute une requête SELECT et renvoie une seule ligne
     * @return array éléments, ou FALSE en cas d'erreur
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
     * Exécuter une méthode d'un objet, même si elle est private.
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
