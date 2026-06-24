<?php

namespace S2low\Helpers;

use DateTime;
use IntlDateFormatter;
use UnexpectedValueException;

class DateHelper
{
    /**
     * @param string $date
     * @param bool $at_midnight
     * @return int
     */
    public function ansiDateToTimestamp($date, $at_midnight = false)
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

    /**
     * @param int $timestamp
     * @return string
     */
    public function TimestampToString($timestamp)
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

    /**
     * @param string $hour
     * @return string|null
     */
    public function getPrettyHours($hour)
    {
        $hours = explode(':', $hour);

        if (count($hours) != 3) {
            return null;
        }

        return $hours[0] . "h " . $hours[1] . "min " . $hours[2] . "s";
    }

    /**
     * @param string|null $date
     * @return int|null
     */
    public function getTimestampFromBDDDate($date)
    {
        if (preg_match("/^([0-9]{4})-([0-9]{2})-([0-9]{2})\s+([0-9]{2}):([0-9]{2}):([0-9]{2}).*$/", $date ?? "", $matches)) {
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

    /**
     * @param string|null $date
     * @param bool $with_hours
     * @return string|null
     */
    public function getDateFromBDDDate($date, $with_hours = false)
    {
        if ($timestamp = $this->getTimestampFromBDDDate($date)) {
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

    /**
     * @param string|null $date
     * @return string|null
     */
    public function getANSIDateFromBDDDate($date)
    {
        if ($timestamp = $this->getTimestampFromBDDDate($date)) {
            return date("Y-m-d", $timestamp);
        }

        return null;
    }

    /**
     * @param string|null $var
     * @param bool $nullable
     * @param string $name
     * @return string|null
     */
    public function checkDate(?string $var, bool $nullable, string $name)
    {
        if ($nullable && is_null($var)) {
            return $var;
        }
        if (!$nullable && is_null($var)) {
            throw new UnexpectedValueException("$name n'est pas une date");
        }
        if (!strtotime($var) && !((is_null($var) || !$var) && $nullable)) {
            throw new UnexpectedValueException("$name n'est pas une date");
        }
        return $var;
    }
}
