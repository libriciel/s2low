<?php

namespace S2low\Tests;

use S2lowLegacy\Model\LogsSQL;
use S2lowLegacy\Model\LogsHistoriqueSQL;

trait LogsHistoriqueSQLTrait
{
    public static int $LOG_ID_TO_DELETE = 42;
    public static int $LOG_ID_NOT_DELETE_LIMIT = 43;
    public static int $LOG_ID_NOT_DELETE_DATE = 44;

    abstract public function getLogHistoriqueSQL(): LogsHistoriqueSQL;

    public function addFixtures(): void
    {
        $this->getLogHistoriqueSQL()->addLog(
            self::$LOG_ID_TO_DELETE,
            '1977-02-01',
            LogsSQL::LEVEL_CRITICAL,
            "actes",
            "TdT",
            "1",
            "SADM",
            "bar",
            "baz"
        );
        $this->getLogHistoriqueSQL()->addLog(
            self::$LOG_ID_NOT_DELETE_LIMIT,
            '1982-01-01',
            LogsSQL::LEVEL_CRITICAL,
            "actes",
            "TdT",
            "1",
            "SADM",
            "bar",
            "baz"
        );
        $this->getLogHistoriqueSQL()->addLog(
            self::$LOG_ID_NOT_DELETE_DATE,
            date('Y-m-d'),
            LogsSQL::LEVEL_CRITICAL,
            "actes",
            "TdT",
            "1",
            "SADM",
            "bar",
            "baz"
        );
    }
}
