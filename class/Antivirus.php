<?php

namespace S2lowLegacy\Class;

use Exception;

class Antivirus
{
    private $shellCommand;
    private $antivirus_command;
    private $filesystem;

    private $last_error;

    public function __construct(
        ShellCommand $shellCommand,
        $antivirus_command,
        \Symfony\Component\Filesystem\Filesystem $filesystem
    ) {
        $this->shellCommand = $shellCommand;
        $this->antivirus_command = $antivirus_command;
        $this->filesystem = $filesystem;
    }

    /**
     * @param $path
     * @return bool
     * @throws Exception
     */
    public function checkArchiveSanity($path, float $timeout = 60)
    {
        $tmpFolder = new TmpFolder();

        $tmp_dir = $tmpFolder->create();
        $new_file = $tmp_dir . "/" . basename($path);

        $this->filesystem->copy($path, $new_file);
        $this->filesystem->chmod($new_file, 0644);

        $ret = $this->shellCommand->exec([$this->antivirus_command,$new_file], $timeout);
        $output = $this->shellCommand->getLastOutput();

        $tmpFolder->delete($tmp_dir);

        if ($ret === 1) {
            $this->last_error = "L'archive est infectée par un virus. Retour de l'antivirus&nbsp;:<br />\n";
            // Format de ligne : /Nom/de/fichier: Nom virus
            foreach (explode("\n", $output) as $line) {
                if (preg_match('/^\/.*: .* FOUND$/', $line)) {
                    $line = explode(":", $line);
                    $this->last_error .= basename($line[0]) . " : " . $line[1] . "<br />\n";
                }
            }
            return false;
        }

        if ($ret !== 0) {
            $message = "Erreur " . $ret . " lors du scan antivirus de l'archive.";
            $this->last_error = $message;
            throw new Exception($message);
        }
        return true;
    }

    public function getLastError()
    {
        return $this->last_error;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function isAlive()
    {
        $ret = $this->shellCommand->exec([$this->antivirus_command, __FILE__]);
        if ($ret !== 0) {
            $output = $this->shellCommand->getLastOutput();
            throw new Exception("Problème avec l'antivirus : $output");
        }
        return true;
    }
}
