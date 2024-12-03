<?php

namespace S2lowLegacy\Class;

require_once(SITEROOT . "class/Versionning.php");

class Layout
{
    public $header;
    public $body;
    public $title;
    public $templateFile;

    /**
   * \brief Méthode permettant de définir le titre du document
   * \param $str chaîne : le titre du document
  */
    public function setTitle($str)
    {
        $this->title =  $str;
    }

  /**
   * \brief Méthode d'ajout de contenu dans l'en-tête du document
   * \param $str chaîne : chaîne de caractères à ajouter dans le document
  */
    public function addHeader($str)
    {
        $this->header .= $str;
    }

  /**
   * \brief Méthode d'ajout de contenu dans le corps du document
   * \param $str chaîne : chaîne de caractères à ajouter dans le document
  */
    public function addBody($str)
    {
        $this->body .= $str;
    }

  /**
   * @deprecated 5.0.42, dead code
   */
    public function setTemplate($template): void
    {
        $this->templateFile = $template;
    }
}
