<?php

namespace S2lowLegacy\Class\Helpers;

use Exception;
use S2lowLegacy\Lib\JSONoutput;

class ResponseHelper
{
    public static $last_error;

    public static function returnAndExit($status, $msg, $redirect = null, $apiMsg = null): never
    {
        $api = RequestHelper::getVarFromPost("api");

        if (empty($api)) {
            $api = RequestHelper::getVarFromGet("api");
        }

        if ($api != null && $api == "1") {
            if ($status == 0) {
                echo "OK\n";
            } else {
                echo "KO\n";
            }

            if ($apiMsg) {
                echo mb_convert_encoding($apiMsg, 'ISO-8859-1') . "\n";
            } elseif (! empty($msg)) {
                echo mb_convert_encoding(get_hecho($msg), 'ISO-8859-1') . "\n";
            }
        } else {
            if ($redirect) {
                $_SESSION["error"] = nl2br($msg);
                if (TESTING_ENVIRONNEMENT) {
                    throw new Exception("Message : $msg");
                }
                header("Location: " . $redirect);  // @codeCoverageIgnore
            } else { // @codeCoverageIgnore
                echo $msg . "\n";
            }
        }
        if (TESTING_ENVIRONNEMENT) {
            throw new Exception($msg);
        }

        exit();  // @codeCoverageIgnore
    }

    public static function sendFileToBrowser($path, $filename, $content_type = null)
    {
        if ($path) {
            if (! file_exists($path)) {
                self::$last_error = "Fichier spécifié introuvable";
                return false;
            }
        }

        if ($content_type) {
            header_wrapper("Content-type: " . $content_type);
        }

        header_wrapper('Content-disposition: attachment; filename="' . $filename . '"');
        header_wrapper("Expires: 0");
        header_wrapper("Cache-Control: must-revalidate, post-check=0,pre-check=0");
        header_wrapper("Pragma: public");

        if ($path) {
            if (! @readfile($path)) {
                self::$last_error = "Erreur lors de la lecture du fichier";
                return false;
            }
        }

        return true;
    }

    /**
     * @throws \Exception
     */
    public static function exitOrDisplayError($api, $erreur_msg, $location)
    {
        if ($api) {
            $jsonOutput = new JSONoutput();
            $jsonOutput->displayErrorAndExit($erreur_msg);
        } else {
            $_SESSION['error'] = $erreur_msg;
            header("Location: $location");
            exit;
        }
    }
}
