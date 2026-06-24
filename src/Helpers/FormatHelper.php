<?php

namespace S2low\Helpers;

use UnexpectedValueException;

class FormatHelper
{
    /**
     * @param string $str
     * @return string
     */
    public function stripSlashes($str)
    {
        return $str;
    }

    /**
     * @param mixed $var
     * @return mixed
     */
    public function getFromBDD($var)
    {
        return $var;
    }

    /**
     * @param string|null $str
     * @return string
     */
    public function escapeForXML($str)
    {
        return str_replace("\"", "\\\"", $str ?? "");
    }

    /**
     * @param mixed $elt
     * @return string
     */
    public function getFromXMLElt($elt)
    {
        return sprintf("%s", $elt);
    }

    /**
     * @param string $str
     * @param int $length
     * @param bool $add_ellipsis
     * @return string
     */
    public function truncateString($str, $length = 40, $add_ellipsis = true)
    {
        $new_str = mb_substr($str, 0, $length);

        if ($add_ellipsis && mb_strlen($new_str) < mb_strlen($str)) {
            $new_str .= "...";
        }

        return $new_str;
    }

    /**
     * @param string|null $var
     * @param bool $nullable
     * @param mixed $name
     * @return string|null
     */
    public function checkInt(?string $var, bool $nullable, $name): ?string
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

    /**
     * @param string $string
     * @param int $length
     * @return string
     */
    public function chunkString($string, $length)
    {
        $result = mb_substr($string, 0, $length);
        if (mb_strlen($string) > 40) {
            $result .= "...";
        }
        return $result;
    }
}
