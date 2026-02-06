<?php

namespace App\Enum;

enum Status: string
{
    case HANDLE = 'HANDLE';
    case ASK = 'ASK';
    case DOWNLOADED = 'DOWNLOADED';
    case COMPLETED = 'COMPLETED';
    case ERROR = 'ERROR';
}
