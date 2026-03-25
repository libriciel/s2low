<?php

namespace App\Enum;

enum Status: string
{
    case HANDLE = 'HANDLE';
    case ASK = 'ASK';
    case BUCKET_FOUND = 'BUCKET_FOUND';
    case DOWNLOADED = 'DOWNLOADED';
    case COMPLETED = 'COMPLETED';
    case ERROR = 'ERROR';
}
