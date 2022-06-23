<?php

require_once(SITEROOT . "class/Versionning.class.php");


class Layout
{
    public $header;
    public $body;
    public $title;

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
   *
   * @param $templateFile: le template full path name correspond to the controller index.php
   * @return no return value
   */
    public function setTemplate($template)
    {
        $this->templateFile = $template;
    }
}
