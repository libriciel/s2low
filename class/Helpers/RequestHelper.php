<?php

namespace S2lowLegacy\Class\Helpers;

use S2lowLegacy\Class\Helpers;
use UnexpectedValueException;

class RequestHelper
{
    public static function getFiles($name, bool $allowGetApiCall = false)
    {
        /* On ne test volontairement pas l'existence pour singer le comportement précédent */
        $result  = $_FILES[$name];

        if (self::isApiCall($allowGetApiCall)) {
            $result['name'] = utf8_encode($result['name']);
        }
        return $result;
    }

    public static function getFilesFromArray($name, bool $allowGetApiCall = false)
    {
        /* On ne test volontairement pas l'existence pour singer le comportement précédent */
        $results  = $_FILES[$name];

        if (self::isApiCall($allowGetApiCall)) {
            foreach ($results['name'] as $key => $result) {
                 $results['name'][$key] = utf8_encode($result);
            }
        }
        return $results;
    }

    public static function getVarFromPost($name, $memorize = false, bool $allowGetApiCall = false)
    {
        $varFromRequest = self::getVarFromRequest($name, "POST", $memorize);

        if (!is_null($varFromRequest) && self::isApiCall($allowGetApiCall) && !is_array($varFromRequest)) {
            $varFromRequest = utf8_encode($varFromRequest);
        }
        return $varFromRequest;
    }

    public static function isApiCall(bool $allowGetApi = false): bool
    {
        // La présence de allowGetApi est un hotfix
        $apiIsSetByPost = self::getVarFromRequest("api", "POST") == 1;
        $apiIsSetByGet = self::getVarFromRequest("api", "GET") == 1;
        return ($apiIsSetByPost || ($apiIsSetByGet && $allowGetApi));
    }

    public static function getIntFromPost($name, $nullable = false, bool $memorize = false)
    {
        return self::checkInt(
            self::getVarFromRequest($name, "POST", $memorize),
            $nullable,
            $name
        );
    }

    public static function getVarFromGet($name, $memorize = false)
    {
        return self::getVarFromRequest($name, "GET", $memorize);
    }

    public static function getIntFromGet($name, $nullable = false)
    {
        return self::checkInt(
            self::getVarFromRequest($name, "GET"),
            $nullable,
            $name
        );
    }

    public static function getDateFromGet($name, $nullable = false)
    {
        return self::checkDate(
            self::getVarFromRequest($name, "GET"),
            $nullable,
            $name
        );
    }

    public static function getVarFromRequest($name, $type, $memorize = false)
    {
        if ($type == "POST") {
            $var = &$_POST;
        } elseif ($type == "GET") {
            $var = &$_GET;
        }

        $ret = (isset($var[$name])) ? $var[$name] : null;

        if (is_array($ret)) {
            foreach ($ret as $key => $value) {
                $ret[$key] = self::stripSlashes($value);
            }
        } else {
            $ret = self::stripSlashes($ret);
        }

        if ($memorize) {
            SessionHelper::putInSession($name, $ret);
        }

        return $ret;
    }

    public static function stripSlashes($str)
    {
        return $str;
    }

    public static function checkInt(?string $var, bool $nullable, $name): ?string
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

    public static function checkDate(?string $var, bool $nullable, string $name)
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
