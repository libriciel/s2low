<?php

/**
 * Class QueryResult
 */

namespace S2lowLegacy\Class;

use Doctrine\DBAL\Result;

class QueryResult
{
    private $result;

    public function __construct(Result $result)
    {
        $this->result = $result;
    }

    /** WTF... en simplifiant ca donne toujours false */
    public function isError()
    {
        return false;
    }

    // Compte les lignes de resultat
    public function num_row()
    {
        return $this->result->rowCount();
    }

    // Compte les colonnes de resultat
    public function num_field()
    {
        return $this->result->columnCount();
    }

    // Retourne la ligne courante resultat ou FALSE si plus de lignes
    public function get_next_row()
    {
        return $this->result->fetchAssociative();
    }

    public function get_all_rows()
    {
        return $this->result->fetchAllAssociative();
    }
}
