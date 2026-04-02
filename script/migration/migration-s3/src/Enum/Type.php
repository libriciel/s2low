<?php

namespace App\Enum;

enum Type: string
{
    case ACTE = 'ACTE';
    case PES_ACQUIT = 'PES_ACQUIT';
    case PES_ALLER = 'PES_ALLER';
    case PES_RETOUR = 'PES_RETOUR';
    case MAIL = 'MAIL';
}
