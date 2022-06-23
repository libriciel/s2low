<?php

/**
 * \class CSVLayout Layout.class.php
 * \brief Classe pour la génération de fichier CSV
 * \author Jérôme Schell <j.schell@alternancesoft.com>
 * \date 17.02.2006
 *
 *
 * Cette classe fournit des méthodes pour la génération de fichiers CSV
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

class CSVLayout extends Layout
{
    /**
     * \brief Méthode d'ajout d'une ligne dans le fichier CSV
     * \param $str chaîne : chaîne de caractères à ajouter dans le document ou tableau de champs qui seront ajoutés séparés par des points virgules
     */
    public function addLine($str)
    {
        if (is_array($str)) {
            $line = implode(";", $str);
        } else {
            $line = $str;
        }

        $line .= "\r\n";

        $this->addBody($line);
    }

    /**
     * \brief Méthode générant l'affichage du document
     */
    public function display()
    {
        $content_type = "text/csv;charset=iso-8859-1";
        if (! empty($this->header)) {
            $content_type .= ";header=present";
        }

        if (! Helpers::sendFileToBrowser(null, "transactions.csv", $content_type)) {
            return false;
        }

        if (! empty($this->header)) {
            echo $this->header . "\r\n";
        }

        echo $this->body;
    }
}
