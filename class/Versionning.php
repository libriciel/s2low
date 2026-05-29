<?php

namespace S2lowLegacy\Class;

class Versionning
{
    private const BUILD_ID = "BUILD_ID";
    private const BUILD_DATE = "BUILD_DATE";
    private const VERSION = "VERSION";

    private $manifestFile;

    public function __construct($manifestFile)
    {
        $this->manifestFile = $manifestFile;
    }

    private function getInfo()
    {
        $revisionFileContent = file_get_contents($this->manifestFile);
        $result = array();
        foreach (explode("\n", $revisionFileContent) as $line) {
            foreach (array(self::BUILD_DATE,self::BUILD_ID,self::VERSION) as $info) {
                if (preg_match("#^$info=(.*)#", $line, $matches)) {
                    $result[$info] = $matches[1];
                }
            }
        }
        return $result;
    }

    private function getSpecificInfo($key)
    {
        $info = $this->getInfo();
        return $info[$key];
    }

    public function getRevision()
    {
        return $this->getSpecificInfo(self::BUILD_ID);
    }

    public function getDate()
    {
        return $this->getSpecificInfo(self::BUILD_DATE);
    }

    public function getVersion()
    {
        return $this->getSpecificInfo(self::VERSION);
    }

    public function getAllInfo()
    {
        $result['version'] = $this->getVersion();
        $result['revision'] = $this->getRevision();
        $result['date'] = $this->getDate();

        $result['version-complete'] =  "Version {$result['version']} - Révision  {$result['revision']} - {$result['date']}" ;
        return $result;
    }
}
