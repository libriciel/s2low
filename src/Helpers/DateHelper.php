<?php

namespace S2low\Helpers;

use DateTime;
use IntlDateFormatter;

class DateHelper
{
    public function ansiDateToTimestamp(string $date, bool $at_midnight = false): int
    {
        $tmp = explode('-', $date);
        $year = (int) $tmp[0];
        $month = (int) $tmp[1];
        $day = (int) $tmp[2];

        if ($at_midnight) {
            $hour = 0;
        } else {
            $hour = 12;
        }

        return mktime($hour, 0, 0, $month, $day, $year);
    }

    public function TimestampToString(int $timestamp): string
    {
        $myDateTime = new DateTime();
        $myDateTime->setTimestamp($timestamp);
        $pattern = "d MMMM YYYY";
        $formatter = new IntlDateFormatter(
            'fr_FR',
            IntlDateFormatter::FULL,
            IntlDateFormatter::FULL,
            'Europe/Paris',
            IntlDateFormatter::GREGORIAN,
            $pattern
        );
        return $formatter->format($myDateTime);
    }

    public function getPrettyHours(string $hour): ?string
    {
        $hours = explode(':', $hour);

        if (count($hours) != 3) {
            return null;
        }

        return $hours[0] . "h " . $hours[1] . "min " . $hours[2] . "s";
    }

    public function getTimestampFromBDDDate(?string $date): ?int
    {
        if (preg_match("/^([0-9]{4})-([0-9]{2})-([0-9]{2})\s+([0-9]{2}):([0-9]{2}):([0-9]{2}).*$/", $date ?? "", $matches)) { //Quickfix php 8
            $year = $matches[1];
            $month = $matches[2];
            $day = $matches[3];
            $hour = $matches[4];
            $min = $matches[5];
            $sec = $matches[6];

            return mktime($hour, $min, $sec, $month, $day, $year);
        }

        return null;
    }

    public function getDateFromBDDDate(?string $date, bool $with_hours = false): ?string
    {
        if ($timestamp = $this->getTimestampFromBDDDate($date)) {
            $myDateTime = new DateTime();
            $myDateTime->setTimestamp($timestamp);
            $pattern = "d MMMM yyyy";//"j F Y";

            if ($with_hours) {
                $pattern = $pattern . " à " . "HH'h'mm'min'ss's'";
            }
            $formatter = new IntlDateFormatter(
                'fr_FR',
                IntlDateFormatter::FULL,
                IntlDateFormatter::FULL,
                'Europe/Paris',
                IntlDateFormatter::GREGORIAN,
                $pattern
            );
            return $formatter->format($myDateTime);
        }

        return null;
    }

    public function getANSIDateFromBDDDate(?string $date): ?string
    {
        if ($timestamp = $this->getTimestampFromBDDDate($date)) {
            $str = date("Y-m-d", $timestamp);

            return $str;
        }

        return null;
    }
}
