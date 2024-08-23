<?php

namespace S2low\Legacy;

class LegacyClassLoader
{
    public function getClassName(string $file): string
    {
        $fp = fopen($file, 'r');
        $class = '';
        $buffer = '';
        $namespace = '';
        while (!$class) {
            if (feof($fp)) {
                break;
            }
            $buffer .= fread($fp, 512);
            if (preg_match('/class\s+(\w+)\s+extends (.*)?S2lowLegacyCommandInSymfonyContainer/', $buffer, $matches)) {
                $class = $matches[1];
            }
            if (preg_match('/namespace\s+([\\\0-9a-zA-Z]*)/', $buffer, $matches)) {
                $namespace = $matches[1];
            }
        }
        if ($class === '' || $namespace === '') {
            return '';
        }
        return "$namespace\\$class";
    }
}
