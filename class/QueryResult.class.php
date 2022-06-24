<?php

/**
 * Class QueryResult
 */
class QueryResult
{
    private $pdoStatement;

    public function __construct(PDOStatement $pdoStatement)
    {
        $this->pdoStatement = $pdoStatement;
    }

    /** WTF... en simplifiant ca donne toujours false */
    public function isError()
    {
        return false;
    }

    // Compte les lignes de resultat
    public function num_row()
    {
        return $this->pdoStatement->rowCount();
    }

    // Compte les colonnes de resultat
    public function num_field()
    {
        return $this->pdoStatement->columnCount();
    }

    // Retourne la ligne courante resultat ou FALSE si plus de lignes
    public function get_next_row()
    {
        return $this->pdoStatement->fetch(PDO::FETCH_ASSOC);
    }

    public function get_all_rows()
    {
        return $this->pdoStatement->fetchAll(PDO::FETCH_ASSOC);
    }
}
