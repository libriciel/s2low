<?php

namespace S2lowLegacy\Class\Helpers;

class CertificateHelper
{
    public static $last_error;

    public static function getAuthorizedCACerts($path = EXTENDED_VALIDCA_PATH)
    {
        $certs = array();

        if (is_dir($path)) {
            if (! $files = scandir($path)) {
                return false;
            }

            foreach ($files as $file) {
                $file = $path . "/" . $file;

                if (is_file($file) && ! is_link($file)) {
                    if (! $cert = @file_get_contents($file)) {
                        self::$last_error .= "Certficate file error in validca:" . $file . "\n";
                        continue;
                    }

                    if ($x509 = openssl_x509_parse($cert)) {
                      //print_r($x509);
                        $certs[] = $x509;
                    }
                }
            }
        }

        return $certs;
    }
}
