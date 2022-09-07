<?php

function legacy_encode_array($array)
{
             // migration UTF-8 : utilisé pour garder le même comportement de l'API
    if (! is_array($array)) {
        return  (string) $array ?? '';
    }
    $result = array();
    foreach ($array as $cle => $value) {
        $result[ (string) $cle] = legacy_encode_array($value);
    }
    return $result;
}


function move_uploaded_file_wrapper($filename, $destination)
{
    if (TESTING_ENVIRONNEMENT) {
        return rename($filename, $destination);
    }
    return move_uploaded_file($filename, $destination);
}

function is_uploaded_file_wrapper($filename)
{
    if (TESTING_ENVIRONNEMENT) {
        return file_exists($filename);
    }
    return is_uploaded_file($filename);
}


function is_valid_email($email)
{
    //Ne supporte pas les adresses email en UTF8
    //http://stackoverflow.com/questions/19522092/should-i-use-filter-var-to-validate-email
    $email_list = explode(",", $email);
    foreach ($email_list as $mail) {
        if (!!!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
    }
    return true;
}

function get_url($url_path)
{
    return trim("/", WEBSITE_SSL) . $url_path;
}
