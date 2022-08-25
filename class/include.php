<?php

$debut = microtime(true);

if (php_sapi_name() != 'cli') {
    session_start();
}

/*
 * Ce fichier à l'air d'être inclu dans tous les scripts S²low
 *
 */

function hecho($message, $quot_style = ENT_QUOTES)
{
    echo get_hecho($message, $quot_style, "utf-8");
}

function get_hecho($message, $quot_style = ENT_QUOTES, $encoding = "utf-8")
{
    return htmlspecialchars($message ?? '', $quot_style, $encoding);
}

if (defined("TESTING_ENVIRONNEMENT") && TESTING_ENVIRONNEMENT) {

    function exit_wrapper($status = "")
    {
        $message = "exit() called";
        if ($status) {
            $message .= " with status $status";
        }
        throw new Exception($message);
    }

    function header_wrapper($string, $replace = true, $http_response_code = null)
    {
        echo "header('$string','$replace','$http_response_code') called\n";
    }

    function sleep_wrapper($seconds)
    {
        //don't sleep
    }

} else {

    function exit_wrapper($status = "")
    {
        exit($status);
    }

    function header_wrapper($string, $replace = true, $http_response_code = null)
    {
        header($string, $replace, $http_response_code);
    }

    function sleep_wrapper($seconds)
    {
        sleep($seconds);
    }
}
