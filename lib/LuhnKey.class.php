<?php

class LuhnKey
{
    public function isValid($number)
    {
        if (strval(intval($number)) != strval($number)) {
            return false;
        }
        $luhn_key = $this->getLuhnKey($number);
        return $this->testLuhnKey($luhn_key);
    }

    public function testLuhnKey($control)
    {
        return $control % 10 == 0;
    }

    public function getLuhnKey($number)
    {
        $sum = 0;
        foreach (array_reverse(mb_str_split($number)) as $i => $chiffre) {
            if ($i % 2 == 1) {
                $chiffre *= 2;
                if ($chiffre > 9) {
                    $chiffre -= 9;
                }
            }
            $sum += $chiffre;
        }
        return $sum;
    }

    public function getValidNumberWithBegin($begin)
    {
        $guess_number = $begin . "0";

        $key = $this->getLuhnKey($guess_number);
        if ($this->testLuhnKey($key)) {
            return $guess_number;
        }

        return $begin . (( 10 - $key % 10 ) % 10);
    }

    public function generateValidNumber($length)
    {
        $number = "";
        for ($i = 0; $i < ($length - 1); $i++) {
            $number = $number . mt_rand(0, 9);
        }
        return $this->getValidNumberWithBegin($number);
    }
}
