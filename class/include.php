<?php

if (!defined('RENDER_STARTING_TIME')) {
    define('RENDER_STARTING_TIME', microtime(true));
}
$debut = microtime(true);

if (php_sapi_name() != 'cli') {
    session_start();
}

/*
 * Ce fichier à l'air d'être inclu dans tous les scripts S²low
 *
 */

function hecho($message, $quot_style = ENT_QUOTES): void
{
    echo get_hecho($message, $quot_style);
}

function get_hecho($message, $quot_style = ENT_QUOTES, $encoding = 'utf-8'): string
{
    return htmlspecialchars($message ?? '', $quot_style, $encoding);
}

if (defined('TESTING_ENVIRONNEMENT') && TESTING_ENVIRONNEMENT) {

    function exit_wrapper($status = ''): never
    {
        $message = 'exit() called';
        if ($status) {
            $message .= " with status $status";
        }
        throw new Exception($message);
    }

    function header_wrapper($string, bool $replace = true, int $http_response_code = 0): void
    {
        echo "header('$string','$replace','$http_response_code') called\n";
    }

    function sleep_wrapper($seconds)
    {
        //don't sleep
    }

    function header_remove_wrapper()
    {
        //don't sleep
    }

} else {

    function exit_wrapper($status = ''): void
    {
        exit($status);
    }

    function header_wrapper($string, bool $replace = true, int $http_response_code = 0): void
    {
        header($string, $replace, $http_response_code);
    }

    function sleep_wrapper($seconds): void
    {
        sleep($seconds);
    }

    function header_remove_wrapper(): void
    {
        header_remove();
    }
}
