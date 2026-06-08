<?php

namespace S2low\Helpers;

class XmlHelper
{
    public function escapeForXML(?string $str): string
    {
        return str_replace("\"", "\\\"", $str ?? ""); // Quickfix php 8
    }

    public function getFromXMLElt(mixed $elt): string
    {
        return sprintf("%s", $elt);
    }
}
