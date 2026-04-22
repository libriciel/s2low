<?php

namespace S2lowLegacy\Class\Helpers;

use DateTime;
use IntlDateFormatter;

class DateHelper
{
    public static function ansiDateToTimestamp($date, $at_midnight = false)
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

    public static function TimestampToString($timestamp)
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

    public static function getPrettyHours($hour)
    {
        $hours = explode(':', $hour);

        if (count($hours) != 3) {
            return null;
        }

        return $hours[0] . "h " . $hours[1] . "min " . $hours[2] . "s";
    }

    public static function getTimestampFromBDDDate($date)
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

    public static function getDateFromBDDDate($date, $with_hours = false)
    {
        if ($timestamp = self::getTimestampFromBDDDate($date)) {
            $myDateTime = new DateTime();
            $myDateTime->setTimestamp($timestamp);
            $pattern = "d MMMM yyyy";

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

    public static function getANSIDateFromBDDDate($date)
    {
        if ($timestamp = self::getTimestampFromBDDDate($date)) {
            $str = date("Y-m-d", $timestamp);

            return $str;
        }

        return null;
    }
}
