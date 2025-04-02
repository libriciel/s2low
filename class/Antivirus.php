<?php

namespace S2lowLegacy\Class;

use Exception;
use S2low\Exceptions\AntivirusCommandException;
use S2low\Exceptions\InfectedFileException;
use Symfony\Component\Filesystem\Filesystem;

class Antivirus
{
    public function __construct(
        private readonly ShellCommand $shellCommand,
        private readonly string $antivirus_command,
        private readonly Filesystem $filesystem
    ) {
    }

    /**
     * @throws Exception
     */
    public function checkFile($path): void
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
            $message = "L'archive est infectée par un virus. Retour de l'antivirus&nbsp;:<br />\n";
            // Format de ligne : /Nom/de/fichier: Nom virus
            foreach (explode("\n", $output) as $line) {
                if (preg_match('/^\/.*: .* FOUND$/', $line)) {
                    $line = explode(':', $line);
                    $message .= basename($line[0]) . ' : ' . $line[1] . "<br />\n";
                }
            }
            throw new InfectedFileException($message);
        }

        if ($ret !== 0) {
            throw new AntivirusCommandException('Erreur ' . $ret . " lors du scan antivirus de l'archive.");
        }
    }
}
