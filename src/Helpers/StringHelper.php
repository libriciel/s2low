<?php

namespace S2low\Helpers;

class StringHelper
{
    public function getFromBDD(mixed $var): mixed
    {
        return $var;
    }

    public function truncateString($str, int $length = 40, bool $add_ellipsis = true): string
    {
        $new_str = mb_substr($str, 0, $length);

        if ($add_ellipsis && mb_strlen($new_str) < mb_strlen($str)) {
            $new_str .= "...";
        }

        return $new_str;
    }

    public function chunkString(string $string, int $length): string
    {
        $result = mb_substr($string, 0, $length);
        if (mb_strlen($string) > 40) {
            $result .= "...";
        }
        return $result;
    }
}
