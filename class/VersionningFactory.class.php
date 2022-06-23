<?php

class VersionningFactory
{
    public static function getInstance()
    {
        $manifest = __DIR__ . "/../manifest.txt";
        $versionning = new Versionning($manifest);
        return $versionning;
    }
}