<?php

namespace App\Enum;

enum Status: string
{
    case ERROR_KEY_NULL = 'ERROR_KEY_NULL';
    case HANDLE = 'HANDLE';
    case ASK = 'ASK';
    case BUCKET_FOUND = 'BUCKET_FOUND';
    case RESTORING = 'RESTORING';
    case DOWNLOADED = 'DOWNLOADED';
    case COMPLETED = 'COMPLETED';
    case ERROR = 'ERROR';
}
