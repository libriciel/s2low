<?php

namespace S2low\Helpers;

use Exception;
use S2lowLegacy\Lib\JSONoutput;

class RequeteHelper
{
    private SessionHelper $sessionHelper;
    private FormatHelper $formatHelper;
    private DateHelper $dateHelper;

    public function __construct(
        SessionHelper $sessionHelper,
        FormatHelper $formatHelper,
        DateHelper $dateHelper
    ) {
        $this->sessionHelper = $sessionHelper;
        $this->formatHelper = $formatHelper;
        $this->dateHelper = $dateHelper;
    }

    /**
     * @param string $name
     * @param bool $allowGetApiCall
     * @return mixed
     */
    public function getFiles($name, bool $allowGetApiCall = false)
    {
        $result  = $_FILES[$name] ?? null;

        if ($result !== null && $this->isApiCall($allowGetApiCall)) {
            $result['name'] = utf8_encode($result['name']);
        }
        return $result;
    }

    /**
     * @param string $name
     * @param bool $allowGetApiCall
     * @return mixed
     */
    public function getFilesFromArray($name, bool $allowGetApiCall = false)
    {
        $results  = $_FILES[$name] ?? null;

        if ($results !== null && $this->isApiCall($allowGetApiCall)) {
            foreach ($results['name'] as $key => $result) {
                $results['name'][$key] = utf8_encode($result);
            }
        }
        return $results;
    }

    /**
     * @param string $name
     * @param bool $memorize
     * @param bool $allowGetApiCall
     * @return mixed
     */
    public function getVarFromPost($name, $memorize = false, bool $allowGetApiCall = false)
    {
        $varFromRequest = $this->getVarFromRequest($name, "POST", $memorize);

        if (!is_null($varFromRequest) && $this->isApiCall($allowGetApiCall) && !is_array($varFromRequest)) {
            $varFromRequest = utf8_encode($varFromRequest);
        }
        return $varFromRequest;
    }

    /**
     * @param bool $allowGetApi
     * @return bool
     */
    private function isApiCall(bool $allowGetApi = false): bool
    {
        $apiIsSetByPost = $this->getVarFromRequest("api", "POST") == 1;
        $apiIsSetByGet = $this->getVarFromRequest("api", "GET") == 1;
        return ($apiIsSetByPost || ($apiIsSetByGet && $allowGetApi));
    }

    /**
     * @param string $name
     * @param bool $nullable
     * @param bool $memorize
     * @return string|null
     */
    public function getIntFromPost($name, $nullable = false, bool $memorize = false)
    {
        return $this->formatHelper->checkInt(
            $this->getVarFromRequest($name, "POST", $memorize),
            $nullable,
            $name
        );
    }

    /**
     * @param string $name
     * @param bool $memorize
     * @return mixed
     */
    public function getVarFromGet($name, $memorize = false)
    {
        return $this->getVarFromRequest($name, "GET", $memorize);
    }

    /**
     * @param string $name
     * @param bool $nullable
     * @return string|null
     */
    public function getIntFromGet($name, $nullable = false)
    {
        return $this->formatHelper->checkInt(
            $this->getVarFromRequest($name, "GET"),
            $nullable,
            $name
        );
    }

    /**
     * @param string $name
     * @param bool $nullable
     * @return string|null
     */
    public function getDateFromGet($name, $nullable = false)
    {
        return $this->dateHelper->checkDate(
            $this->getVarFromRequest($name, "GET"),
            $nullable,
            $name
        );
    }

    /**
     * @param string $name
     * @param string $type
     * @param bool $memorize
     * @return mixed
     */
    public function getVarFromRequest($name, $type, $memorize = false)
    {
        if ($type == "POST") {
            $var = &$_POST;
        } elseif ($type == "GET") {
            $var = &$_GET;
        } else {
            $var = [];
        }

        $ret = (isset($var[$name])) ? $var[$name] : null;

        if (is_array($ret)) {
            foreach ($ret as $key => $value) {
                $ret[$key] = $this->formatHelper->stripSlashes($value);
            }
        } else {
            $ret = $this->formatHelper->stripSlashes($ret);
        }

        if ($memorize) {
            $this->sessionHelper->putInSession($name, $ret);
        }

        return $ret;
    }

    /**
     * @param int $status
     * @param string $msg
     * @param string|null $redirect
     * @param string|null $apiMsg
     * @return never
     * @throws Exception
     */
    public function returnAndExit($status, $msg, $redirect = null, $apiMsg = null): never
    {
        $api = $this->getVarFromPost("api");

        if (empty($api)) {
            $api = $this->getVarFromGet("api");
        }

        if ($api != null && $api == "1") {
            if ($status == 0) {
                // Succès
                echo "OK\n";
            } else {
                echo "KO\n";
            }

            if ($apiMsg) {
                echo mb_convert_encoding($apiMsg, 'ISO-8859-1') . "\n";
            } elseif (! empty($msg)) {
                echo mb_convert_encoding(get_hecho($msg), 'ISO-8859-1') . "\n";
            }
        } else {
            if ($redirect) {
                $_SESSION["error"] = nl2br($msg);
                if (TESTING_ENVIRONNEMENT) {
                    throw new Exception("Message : $msg");
                }
                header("Location: " . $redirect);  // @codeCoverageIgnore
            } else { // @codeCoverageIgnore
                echo $msg . "\n";
            }
        }
        if (TESTING_ENVIRONNEMENT) {
            throw new Exception($msg);
        }

        exit();   // @codeCoverageIgnore
    }

    /**
     * @param array $params
     * @return string
     */
    public function getURLWithParam($params)
    {
        $args = $_SERVER["QUERY_STRING"] ?? '';

        foreach ($params as $param => $value) {
            // Suppression du paramètre s'il existe déjà dans l'URL
            $args = preg_replace("/&?" . $param . "=[^&]+/", "", $args);
            // Suppression d'un éventuel & résiduel au début de la chaîne
            $args = preg_replace("/^&/", "", $args);
            // Détermination du séparateur pour ajouter notre paramètre
            $sep = (mb_strlen($args) > 0) ? "&" : "";

            $args .= $sep . $param . "=" . $value;
        }

        // Remplacement des & par &amp; (XHTML)
        $args = preg_replace("/&/", "&amp;", $args);

        return $this->getLink(($_SERVER["PHP_SELF"] ?? '') . "?" . $args);
    }

    /**
     * @param string $relativePath
     * @return string
     */
    public function getLink(string $relativePath): string
    {
        $url = trim(defined('WEBSITE_SSL') ? WEBSITE_SSL : '', "/");
        $relativePath = ltrim($relativePath, "/");
        return $url . "/" . $relativePath;
    }

    /**
     * @param mixed $api
     * @param string $erreur_msg
     * @param string $location
     * @return void
     * @throws Exception
     */
    public function exitOrDisplayError($api, $erreur_msg, $location)
    {
        if ($api) {
            $jsonOutput = new JSONoutput();
            $jsonOutput->displayErrorAndExit($erreur_msg);
        } else {
            $_SESSION['error'] = $erreur_msg;
            header_wrapper("Location: $location");
            exit_wrapper();
        }
    }
}
