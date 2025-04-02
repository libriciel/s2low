<?php

namespace S2lowLegacy\Class;

use Exception;
use Symfony\Component\Filesystem\Filesystem;

class Antivirus
{
    private $last_error;

    public function __construct(
        private readonly ShellCommand $shellCommand,
        private readonly string $antivirus_command,
        private readonly Filesystem $filesystem
    ) {
    }

    /**
     * @throws Exception
     */
    public function checkFile($path): bool
    {
        $tmpFolder = new TmpFolder();

        $tmp_dir = $tmpFolder->create();
        $new_file = $tmp_dir . '/' . basename($path);

        $this->filesystem->copy($path, $new_file);
        $this->filesystem->chmod($new_file, 0644);

        $ret = $this->shellCommand->exec([$this->antivirus_command,$new_file]);
        $output = $this->shellCommand->getLastOutput();

        $tmpFolder->delete($tmp_dir);

        if ($ret === 1) {
            $this->last_error = "L'archive est infectée par un virus. Retour de l'antivirus&nbsp;:<br />\n";
            // Format de ligne : /Nom/de/fichier: Nom virus
            foreach (explode("\n", $output) as $line) {
                if (preg_match('/^\/.*: .* FOUND$/', $line)) {
                    $line = explode(':', $line);
                    $this->last_error .= basename($line[0]) . ' : ' . $line[1] . "<br />\n";
                }
            }
            return false;
        }

        if ($ret !== 0) {
            $message = 'Erreur ' . $ret . " lors du scan antivirus de l'archive.";
            $this->last_error = $message;
            throw new Exception($message);
        }
        return true;
    }

    public function getLastError()
    {
        return $this->last_error;
    }
}
