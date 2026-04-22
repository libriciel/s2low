<?php

/**
 * \class CSVLayout Layout.class.php
 * \brief Classe pour la g�n�ration de fichier CSV
 * \author J�r�me Schell <j.schell@alternancesoft.com>
 * \date 17.02.2006
 *
 *
 * Cette classe fournit des m�thodes pour la g�n�ration de fichiers CSV
 *
 * Modifications :
 * Auteur   Date       Commentaire
 *
 */

namespace S2lowLegacy\Class;

class CSVLayout extends Layout
{
    /**
     * \brief M�thode d'ajout d'une ligne dans le fichier CSV
     * \param $str cha�ne : cha�ne de caract�res � ajouter dans le document ou tableau de champs qui seront ajout�s s�par�s par des points virgules
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
     * \brief M�thode g�n�rant l'affichage du document
     */
    public function display()
    {
        $content_type = "text/csv;charset=iso-8859-1";
        if (! empty($this->header)) {
            $content_type .= ";header=present";
        }

        if (! \S2lowLegacy\Class\Helpers\ResponseHelper::sendFileToBrowser(null, "transactions.csv", $content_type)) {
            return false;
        }

        if (! empty($this->header)) {
            echo $this->header . "\r\n";
        }

        echo $this->body;
    }
}
