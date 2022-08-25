<?php

namespace S2low\Services;

use S2lowLegacy\Class\S2lowLogger;
use S2lowLegacy\Model\LogsHistoriqueSQL;
use Symfony\Component\Filesystem\Filesystem;

class LogTimestampTokenGarbage
{
    private string $old_timestamp_token_directory;
    private int $timestamp_token_retention_nb_days;
    private LogsHistoriqueSQL $logsHistoriqueSQL;
    private S2lowLogger $s2lowLogger;

    public function __construct(
        string $old_timestamp_token_directory,     //Quickfix PHP 8
        int $timestamp_token_retention_nb_days,     //Quickfix PHP 8
        LogsHistoriqueSQL $logsHistoriqueSQL,
        S2lowLogger $s2lowLogger
    ) {
        $this->timestamp_token_retention_nb_days = $timestamp_token_retention_nb_days;
        $this->old_timestamp_token_directory = $old_timestamp_token_directory;
        $this->logsHistoriqueSQL = $logsHistoriqueSQL;
        $this->s2lowLogger = $s2lowLogger;
    }

    public function getInfo(int $limit = 0)
    {

        $sqlQuery = $this->logsHistoriqueSQL->getLogOlderThanNbDaysWithTimestamp(
            $this->timestamp_token_retention_nb_days,
            $limit
        );
        $nb_result = 0;
        $min = "9999-99-99";
        $max = "1970-01-01";
        $min_data = [];
        $max_data = [];
        while ($sqlQuery->hasMoreResult()) {
            $log_info = $sqlQuery->fetch();

            $nb_result++;
            if ($min > $log_info['date']) {
                $min = $log_info['date'];
                $min_data = $log_info;
            }
            if ($max < $log_info['date']) {
                $max = $log_info['date'];
                $max_data = $log_info;
            }
        }

        return ['older-than' => $this->timestamp_token_retention_nb_days,'limit' => $limit,'nb_result' => $nb_result,'min_date' => $min_data,'max_date' => $max_data];
    }

    public function extractAndDelete(int $limit = 0): void
    {
        $sqlQuery = $this->logsHistoriqueSQL->getLogOlderThanNbDaysWithTimestamp(
            $this->timestamp_token_retention_nb_days,
            $limit
        );
        $nb_result = 0;
        while ($sqlQuery->hasMoreResult()) {
            $nb_result++;
            $log_info = $sqlQuery->fetch();
            $this->saveTimestamp($log_info['id'], $log_info['date'], $log_info['timestamp']);
            $this->logsHistoriqueSQL->deleteTimestamp($log_info['id']);
        }
        $this->s2lowLogger->notice("$nb_result line(s) has been processed");
    }

    private function saveTimestamp(int $log_id, string $date, string $timestamp): void
    {
        $filesystem = new Filesystem();

        $filepath = sprintf(
            "%s/%s/%s.pem",
            $this->old_timestamp_token_directory,
            date("Y/m/d", strtotime($date)),
            $log_id
        );

        $filesystem->dumpFile($filepath, $timestamp);
        $this->s2lowLogger->debug("Save timestamp for id $log_id in file $filepath");
    }

    /**
     * @return string
     */
    public function getOldTimestampTokenDirectory(): string
    {
        return $this->old_timestamp_token_directory;
    }

    /**
     * @param string $old_timestamp_token_directory
     */
    public function setOldTimestampTokenDirectory(string $old_timestamp_token_directory): void
    {
        $this->old_timestamp_token_directory = $old_timestamp_token_directory;
    }

    /**
     * @return int
     */
    public function getTimestampTokenRetentionNbDays(): int
    {
        return (int) $this->timestamp_token_retention_nb_days;
    }

    /**
     * @param int $timestamp_token_retention_nb_days
     */
    public function setTimestampTokenRetentionNbDays(int $timestamp_token_retention_nb_days): void
    {
        $this->timestamp_token_retention_nb_days = $timestamp_token_retention_nb_days;
    }
}
