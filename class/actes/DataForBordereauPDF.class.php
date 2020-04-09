<?php


class DataForBordereauPDF
{
    /** @var string  */
    private $texteCollectivite;
    /** @var string  */
    private $texteUtilisateur;
    /** @var array */
    private $contenuTableau;
    /** @var array */
    private $fichier_table;

    private $cycle_table;

    /**
     * @return string
     */
    public function getTexteCollectivite(): string
    {
        return $this->texteCollectivite;
    }

    /**
     * @return string
     */
    public function getTexteUtilisateur(): string
    {
        return $this->texteUtilisateur;
    }

    /**
     * @return mixed
     */
    public function getContenuTableau()
    {
        return $this->contenuTableau;
    }

    /**
     * @return mixed
     */
    public function getFichierTable()
    {
        return $this->fichier_table;
    }

    /**
     * @return mixed
     */
    public function getCycleTable()
    {
        return $this->cycle_table;
    }

    public function setTexteCollectivite( string $nameCollectivite)
    {
        $this->texteCollectivite = "Collectivité : ".$nameCollectivite;
    }

    public function setTexteUtilisateur($userName, $userGivenName)
    {
        $this->texteUtilisateur = "Utilisateur : ".$userName." ".$userGivenName;
    }

    public function setContenuTableau(array $initDataTable)
    {
        $this->contenuTableau = $initDataTable;
    }

    public function setFichierTable(array $initDatafichier_table)
    {
        $this->fichier_table = $initDatafichier_table;
    }

    public function setCycleTable(array $initcycle_table)
    {
        $this->cycle_table = $initcycle_table;
    }
}