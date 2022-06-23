<?php

//Note : SoapClient crée un fichier de cache du WSDL, voir http://www.php.net/manual/en/soap.configuration.php

class SoapClientFactory
{
    public function getInstance($wsdl, array $options = array(), $is_jax_ws = false)
    {
        return new NotBuggySoapClient($wsdl, $options, $is_jax_ws);
    }
}

$soapErrorException = null;

function soapErrorHandler($errno, $errstr, $errfile, $errline, $errcontext)
{
    global $soapErrorException;
    $soapErrorException = soapErrorAdd($errstr, $errno);
    return false;
}

function soapErrorAdd($errstr, $errno = 0)
{
    global $soapErrorException;
    $cause = $soapErrorException;
    $error = $errstr;
    if ($errno != 0) {
        $error = '(' . $errno . ') ' . $error;
    }
    if ($cause) {
        $error .= " - Cause : " . $cause;
    }
    return $error;
}

