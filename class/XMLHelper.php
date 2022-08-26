<?php

class XMLHelper
{
    public static function convertToIsoAndEscape(string $utf8_encoded_text)
    {
        $result = htmlspecialchars(utf8_decode($utf8_encoded_text) ?? "", ENT_NOQUOTES, "iso-8859-1");
        return $result;
    }

    public static function cp1252_to_iso88591($text)
    {
        $cp1252_map = array(
            "&#8211;"  => "-",
            "&#8212;"  => "-",
            "&#8216;"  => "'",
            "&#8217;"  => "'",
            "&#8218;"  => ",",
            "&#8220;"  => '"',
            "&#8221;"  => '"',
            "&#8222;"  => '"',
            "&#8224;"  => '"',
            "&#8230;"  => "..."
        );
        return strtr($text, $cp1252_map);
    }
}
