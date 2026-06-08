<?php

namespace S2low\Helpers;

use UnexpectedValueException;

class RequestHelper
{
    private SessionHelper $sessionHelper;

    public function __construct(?SessionHelper $sessionHelper = null)
    {
        $this->sessionHelper = $sessionHelper ?? new SessionHelper();
    }

    public function getFiles(string $name, bool $allowGetApiCall = false)
    {
        /* On ne test volontairement pas l'existence pour singer le comportement précédent */
        $result = $_FILES[$name];

        if ($this->isApiCall($allowGetApiCall)) {
            $result['name'] = utf8_encode($result['name']);
        }
        return $result;
    }

    public function getFilesFromArray(string $name, bool $allowGetApiCall = false)
    {
        /* On ne test volontairement pas l'existence pour singer le comportement précédent */
        $results = $_FILES[$name];

        if ($this->isApiCall($allowGetApiCall)) {
            foreach ($results['name'] as $key => $result) {
                $results['name'][$key] = utf8_encode($result);
            }
        }
        return $results;
    }

    public function getVarFromPost(string $name, bool $memorize = false, bool $allowGetApiCall = false)
    {
        $varFromRequest = $this->getVarFromRequest($name, "POST", $memorize);

        if (!is_null($varFromRequest) && $this->isApiCall($allowGetApiCall) && !is_array($varFromRequest)) {
            $varFromRequest = utf8_encode($varFromRequest);
        }
        return $varFromRequest;
    }

    public function isApiCall(bool $allowGetApi = false): bool
    {
        // La présence de allowGetApi est un hotfix
        // returnAndExit considère que l'on utilise l'API à partir du moment ou api est spécifiée à 1 dans post
        // ou à 1 dans get mais pas à 0 dans post.
        $apiIsSetByPost = $this->getVarFromRequest("api", "POST") == 1;
        $apiIsSetByGet = $this->getVarFromRequest("api", "GET") == 1;
        return ($apiIsSetByPost || ($apiIsSetByGet && $allowGetApi));
    }

    public function getIntFromPost(string $name, bool $nullable = false, bool $memorize = false)
    {
        return $this->checkInt(
            $this->getVarFromRequest($name, "POST", $memorize),
            $nullable,
            $name
        );
    }

    public function getVarFromGet(string $name, bool $memorize = false)
    {
        return $this->getVarFromRequest($name, "GET", $memorize);
    }

    public function getIntFromGet(string $name, bool $nullable = false)
    {
        return $this->checkInt(
            $this->getVarFromRequest($name, "GET"),
            $nullable,
            $name
        );
    }

    public function getDateFromGet(string $name, bool $nullable = false)
    {
        return $this->checkDate(
            $this->getVarFromRequest($name, "GET"),
            $nullable,
            $name
        );
    }

    public function getVarFromRequest(string $name, string $type, bool $memorize = false)
    {
        if ($type == "POST") {
            $var = &$_POST;
        } elseif ($type == "GET") {
            $var = &$_GET;
        }

        $ret = (isset($var[$name])) ? $var[$name] : null;

        if (is_array($ret)) {
            foreach ($ret as $key => $value) {
                $ret[$key] = $this->stripSlashes($value);
            }
        } else {
            $ret = $this->stripSlashes($ret);
        }

        if ($memorize) {
            $this->sessionHelper->putInSession($name, $ret);
        }

        return $ret;
    }

    public function stripSlashes($str)
    {
        //Suite à la deprecation de get_magic_quotes_gpc()
        /*if (get_magic_quotes_gpc() == 1) {
          return stripslashes($str);
        } else {
          return $str;
        }*/
        return $str;
    }

    public function checkInt(?string $var, bool $nullable, string $name): ?string
    {
        if (is_null($var) && !$nullable) {
            throw new UnexpectedValueException("$name est null ");
        }
        if (is_null($var)) {
            return null;
        }
        if ($var === '' && $nullable) {
            return '';
        }
        if (!ctype_digit($var) && !(is_null($var) && $nullable)) {
            throw new UnexpectedValueException("$name n'est pas un entier");
        }
        return $var;
    }

    public function checkDate(?string $var, bool $nullable, string $name)
    {
        if ($nullable && is_null($var)) {
            return $var;
        }
        if (!$nullable && is_null($var)) {
            throw new UnexpectedValueException("$name n'est pas une date");
        }
        if (!strtotime($var) && !((is_null($var) || !$var ) && $nullable)) {
            throw new UnexpectedValueException("$name n'est pas une date");
        }
        return $var;
    }
}
