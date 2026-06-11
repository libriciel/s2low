<?php

namespace S2low\Enum;

enum IncomingFileHandlingStatus
{
    case Success;
    case AnalysisFailed;
    case SavingFailed;
}
