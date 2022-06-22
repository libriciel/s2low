<?php

require_once __DIR__ . "/../ext/Parsedown.php";
require_once __DIR__ . "/../ext/HTMLPurifier.standalone.php";

class MessageAdmin
{
    public const ETAT_EN_COURS_DE_REDACTION = 0;
    public const ETAT_PUBLIE = 1;
    public const ETAT_RETIRE = 2;

    public const NIVEAU_INFO = 1;
    public const NIVEAU_WARNING = 2;
    public const NIVEAU_DANGER = 3;


    public $message_id;
    public $titre;
    public $niveau = 0;
    public $message;
    public $date_publication;
    public $date_retrait;
    public $user_id;
    public $user_id_publieur;
    public $user_id_retireur;
    public $is_publie;
    public $is_retire;

    public $user_name;
    public $user_publieur_name;
    public $user_retireur_name;

    public function getMessage()
    {
        $parsedown = new Parsedown();
        $result = $parsedown->parse($this->message);
        $purifyconfig = HTMLPurifier_Config::createDefault();
        $HTMLPurifier = new HTMLPurifier($purifyconfig);
        $result = $HTMLPurifier->purify($result);
        return $result;
    }

    public function getEtat()
    {
        if ($this->is_retire) {
            return self::ETAT_RETIRE;
        }
        if ($this->is_publie) {
            return self::ETAT_PUBLIE;
        }
        return self::ETAT_EN_COURS_DE_REDACTION;
    }

    public function getEtatLibelle()
    {
        $libelle = ['en cours de rédaction','publié','retiré'];
        return $libelle[$this->getEtat()];
    }

    public function displayEtatLabel()
    {
        $label_type = ['label-warning','label-success','label-danger'];
        ?>
        <span class="label <?php echo $label_type[$this->getEtat()] ?>">
            <?php hecho($this->getEtatLibelle()) ?>
        </span>
        <?php
    }

    public function getLibelleNiveau()
    {
        return [1 => 'Information',"Attention","Danger"];
    }

    private function getNiveauCSS()
    {
        $niveau = [0 => 'info',1 => 'info','warning','danger'];
        return $niveau[$this->niveau];
    }

    public function displayMessage()
    {
        ?>
        <br/><br/>
        <div class="alert alert-<?php echo $this->getNiveauCSS() ?> message-admin">
            <h2><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>&nbsp;&nbsp;
            <?php hecho($this->titre) ?></h2>
                <?php echo $this->getMessage() ?>
            <br/><br/>
        </div>
        <br/><br/>
        <?php
    }

    public function displayTitre()
    {
        if (! $this->titre) {
            return;
        }
        ?>
        <br/>
        <a class="label label-<?php echo $this->getNiveauCSS() ?>" href="/"><?php hecho($this->titre) ?></a></a>&nbsp;&nbsp;
        <br/><br/>
        <?php
    }
}