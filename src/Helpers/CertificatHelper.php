<?php

namespace S2low\Helpers;

use S2lowLegacy\Class\Helpers;

class CertificatHelper
{
    private string $extendedValidcaPath;

    public function __construct(string $rgs_validca_path = '')
    {
        $this->extendedValidcaPath = $rgs_validca_path ?: (defined('EXTENDED_VALIDCA_PATH') ? EXTENDED_VALIDCA_PATH : '');
    }

    /**
     * @param string|null $path
     * @return array|bool
     */
    public function getAuthorizedCACerts($path = null)
    {
        $path = $path ?? $this->extendedValidcaPath;
        $certs = array();

        if (is_dir($path)) {
            if (! $files = scandir($path)) {
                return false;
            }

            foreach ($files as $file) {
                $file = $path . "/" . $file;

                if (is_file($file) && ! is_link($file)) {
                    if (! $cert = @file_get_contents($file)) {
                        Helpers::$last_error .= "Certficate file error in validca:" . $file . "\n";
                        continue;
                    }

                    if ($x509 = openssl_x509_parse($cert)) {
                        $certs[] = $x509;
                    }
                }
            }
        }

        return $certs;
    }
}
