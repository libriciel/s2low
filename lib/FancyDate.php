<?php

namespace S2lowLegacy\Lib;

use S2lowLegacy\Class\Helpers;

class FancyDate
{
    public function getDateFrancais($date)
    {
        if (! $date) {
            return false;
        }
        return \S2lowLegacy\Class\Helpers\DateHelper::TimestampToString(strtotime($date));
    }

    public function getDateHeureFrancais($date)
    {
        if (! $date) {
            return false;
        }
        return strftime("%e %B %Y %H:%M:%S", strtotime($date));
    }

    public function getMois($date)
    {
        return strftime("%B %Y", strtotime($date));
    }
}
