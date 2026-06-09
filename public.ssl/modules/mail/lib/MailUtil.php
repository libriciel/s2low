<?php

/**
 * \class MailUtil.class.php
 * \brief fonction commun pour envoiyer les email.
 *
 * Cette classe fournit des méthodes d'envois email de module mail.
 *
 * \author TH ,JMontiel
 * \date :23-04-2008
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

namespace S2lowLegacy\Mail;

use Psr\Log\LoggerInterface;
use ZipArchive;

require_once SITEROOT . '/class/include.php';


class MailUtil
{
    public $errorMsg;

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * \brief extrait un tableau de fichier à partir d'un repertoire
     * \param $root le chemin racine
     * \return array le tableau des fichiers (n'inclue pas les repertoire)
     */
    public function path2array($root)
    {
        $result = array();
        $dh = @opendir($root);
        if (false === $dh) {
            return $result;
        }

        while ($file = readdir($dh)) {
            if ("." == $file || ".." == $file) {
                continue;
            }

            if (is_dir($root . "/" . $file)) {
                $result += $this->path2array($root . "/" . $file);
            } else {
                array_push($result, $root . "/" . $file);
            }
        }
        closedir($dh);
        return $result;
    }

    /**
     * \brief   zip les fichiers dont le chemin est dans array_path et
     *          met le résultat dans $file
     * \param   array_file array tableau des fichiers à zipper
     * \param   $file string nom du fichier de sortie
     * \return  true si ok false sinon. $this->errorMsg contient le message d'erreur
     */
    public function zip($array_file, $file)
    {
        $zip = new ZipArchive();
        $res = $zip->open($file, ZipArchive::CREATE);

        if (! $res) {
            $this->errorMsg = "Impossible de créer l'archive zip $file : erreur " . $res;
            $this->logger->error($this->errorMsg);
            return false;
        }

        foreach ($array_file as $fichier) {
            if (!$zip->addFile($fichier, basename($fichier))) {
                $this->errorMsg = "Impossible de mettre le fichier $fichier dans l'archive $file ";
                $this->logger->error($this->errorMsg);
                return false;
            }
        }
        $zip->close();
        return true;
    }

    public function GetMailMessage()
    {
        // Précédemment il y avait un truc très limité pour tester la boite de retour mais ce n'était a priori pas utilisé
        return null;
    }
}
