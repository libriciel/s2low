<?php

namespace S2lowLegacy\Class\Helpers;

class StringHelper
{
    public static function getFromBDD($var)
    {
        return $var;
    }

    public static function escapeForXML($str)
    {
        return str_replace("\"", "\\\"", $str ?? ""); // Quickfix php 8
    }

    public static function getFromXMLElt($elt)
    {
        return sprintf("%s", $elt);
    }

    public static function truncateString($str, $length = 40, $add_ellipsis = true)
    {
        $new_str = mb_substr($str, 0, $length);

        if ($add_ellipsis && mb_strlen($new_str) < mb_strlen($str)) {
            $new_str .= "...";
        }

        return $new_str;
    }

    public static function chunkString($string, $length)
    {
        $result = mb_substr($string, 0, $length);
        if (mb_strlen($string) > 40) {
            $result .= "...";
        }
        return $result;
    }

    public static function genTempName($length = 8, $prefix = true)
    {
        if ($prefix) {
            $tmp = "__tmp__";
        } else {
            $tmp = "";
        }

        for ($i = 0; $i < $length; $i++) {
            $tmp .= rand(1, 9);
        }

        return $tmp;
    }
}
