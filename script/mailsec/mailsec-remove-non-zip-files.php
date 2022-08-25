<?php

/**
 * Permet de supprimer tous les fichiers du repertoire mailsec qui ne sont pas "mail.zip" car quand on en a besoin on
 * les décompresse désormais à la volée.
 *
 * il faut appeller le script avec "ok" derrière afin qu'il supprime bien les fichiers
 *
 */

use S2lowLegacy\Class\S2lowLogger;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;

require_once(__DIR__ . "/../../init/init.php");
$s2lowLogger = \S2lowLegacy\Class\LegacyObjectsManager::getLegacyObjectInstancier()->get(S2lowLogger::class);

$s2lowLogger->enableStdOut();

$confirm = ($argv[1] ?? false) === 'ok';

$finder = new Finder();
$finder->in(MAIL_FILES_UPLOAD_ROOT . "/*")->files()->notName("mail.zip");

$filesystem = new Filesystem();

foreach ($finder->getIterator() as $file) {
    $s2lowLogger->info("Removing " . $file->getRealPath() . " \n");
    if ($confirm) {
        $filesystem->remove($file->getRealPath());
    }
}
