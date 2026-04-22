<?php

namespace S2lowLegacy\Class\Helpers;

class UrlHelper
{
    public static function getURLWithParam($params)
    {
        $args = $_SERVER["QUERY_STRING"] ?? "";

        foreach ($params as $param => $value) {
            $args = preg_replace("/&?" . $param . "=[^&]+/", "", $args);
            $args = preg_replace("/^&/", "", $args);
            $sep = (mb_strlen($args) > 0) ? "&" : "";

            $args .= $sep . $param . "=" . $value;
        }

        $args = preg_replace("/&/", "&amp;", $args);

        $url = self::getLink($_SERVER["PHP_SELF"] . "?" . $args);

        return $url;
    }

    public static function getLink(string $relativePath): string
    {
        $url = trim(WEBSITE_SSL, "/");
        $relativePath = ltrim($relativePath, "/");
        return $url . "/" . $relativePath;
    }
}
