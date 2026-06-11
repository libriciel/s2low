<?php

namespace PHPUnit\helios;

enum PesTestFile : string
{
    case PES_RETOUR = 'pes_retour.xml';
    case PES_RETOUR_NON_ABONNE = 'pes_retour_nonabonne.xml';
    case PES_ACQUIT = 'pes_acquit.xml';
    case PES_ACQUIT_NOT_VALID = 'pes_acquit_not_valid.xml';
    case PES_ACQUIT_NOT_VALID_WITHOUT_COD_COL = 'pes_acquit_not_valid_without_cod_col.xml';
    case PES_ACQUIT_NOT_VALID_NOT_LINKED_TO_TRANSACTION = 'pes_acquit_wrongXsd_Not_Linked_To_Transaction.xml';
    case WRONG_ROOT_NOT_LINKED_TO_TRANSACTION = 'wrong_root.xml';
    case NOT_XML = 'not_pes.xml';

    public function getPath()
    {
        return __DIR__ . '/fixtures/' . $this->value;
    }

    public function getFilename()
    {
        return $this->value;
    }

    public function getFile()
    {
        return $this->value;
    }
}
