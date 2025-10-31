<?php

namespace S2low\Services;

use Symfony\Component\Finder\Finder;

class FinderFactory
{
    public function __construct(
        private readonly string $actes_files_upload_root,
        private readonly string $helios_files_upload_root,
        private readonly string $helios_responses_root,
        private readonly string $mail_files_upload_root,
        private readonly string $pesAcquitFilePattern,
        private readonly string $pesRetourFilePattern,
        //        private readonly string $mail_files_without_transac_dir
    ) {
    }

    public function createActeEnveloppeFinder(): Finder
    {
        $finder = new Finder();
        $finder->in($this->actes_files_upload_root)->name('*.tar.gz');
        return $finder;
    }

    public function createPesAllerFinder(): Finder
    {
        $finder = new Finder();
        $finder->in($this->helios_files_upload_root);
        return $finder;
    }

    public function createPesRetourFinder(): Finder
    {
        $finder = new Finder();
        $finder->in($this->helios_responses_root)->name($this->pesRetourFilePattern);
        return $finder;
    }

    public function createPesAcquitFinder(): Finder
    {
        $finder = new Finder();
        $finder->in($this->helios_responses_root)->name($this->pesAcquitFilePattern);
        return $finder;
    }

    public function createMailSecFinder(): Finder
    {
        $finder = (new Finder())
            ->in($this->mail_files_upload_root)
            ->name('mail.zip');

//        $dirtemp = $this->mail_files_without_transac_dir;
//
//        $relative_without_transac_dir = $this->getPathRelativeToUploadDir($dirtemp);
//
//        if ($relative_without_transac_dir === $this->mail_files_without_transac_dir) {
//            // Le répertoire contenant les fichiers sans transaction n'est pas contenu dans
//            // le répertoire contenant l'ensemble des fichiers
//            return $finder;
//        }

        // Si le répertoire contenant les fichiers sans transaction est contenu dans
        // le répertoire contenant l'ensemble des fichiers, on doit l'exclure.


        return $finder;

//        return $finder->exclude($relative_without_transac_dir);
    }
}
