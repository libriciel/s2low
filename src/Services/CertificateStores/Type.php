<?php

namespace S2low\Services\CertificateStores;

enum Type: string
{
    case RGS = 'RGS';
    case EXTENDED = 'EXTENDED';
    case DEFAULT = 'DEFAULT';
}
