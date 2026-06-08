<?php

namespace S2low\Helpers;

class UrlHelper
{
    /**
     * @param array $params
     * @return string
     */
    public function getURLWithParam(array $params): string
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

        $url = $this->getLink(($_SERVER["PHP_SELF"] ?? '') . "?" . $args);

        return $url;
    }

    /**
     * @param string $relativePath
     * @return string
     */
    public function getLink(string $relativePath): string
    {
        $url = trim(WEBSITE_SSL, "/");
        $relativePath = ltrim($relativePath, "/");
        return $url . "/" . $relativePath;
    }
}
