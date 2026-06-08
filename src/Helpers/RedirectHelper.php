<?php

namespace S2low\Helpers;

use Exception;
use S2lowLegacy\Lib\JSONoutput;

class RedirectHelper
{
    private RequestHelper $requestHelper;
    private string $lastError = '';

    public function __construct(?RequestHelper $requestHelper = null)
    {
        $this->requestHelper = $requestHelper ?? new RequestHelper();
    }

    public function getLastError(): string
    {
        return $this->lastError;
    }

    /**
     * @param int $status
     * @param string $msg
     * @param string|null $redirect
     * @param string|null $apiMsg
     * @return never
     * @throws Exception
     */
    public function returnAndExit($status, $msg, $redirect = null, $apiMsg = null): never
    {
        // Détection si appel par API C ou formulaire Web (d'abord en POST puis en GET)
        $api = $this->requestHelper->getVarFromPost("api");

        if (empty($api)) {
            $api = $this->requestHelper->getVarFromGet("api");
        }

        if ($api != null && $api == "1") {
            if ($status == 0) {
                // Succès
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

    /**
     * @throws Exception
     */
    public function exitOrDisplayError($api, $erreur_msg, $location)
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

    public function sendFileToBrowser($path, $filename, $content_type = null)
    {
        if ($path) {
            if (! file_exists($path)) {
                $this->lastError = "Fichier spécifié introuvable";
                return false;
            }
        }

        if ($content_type) {
            header_wrapper("Content-type: " . $content_type);
        }

        header_wrapper('Content-disposition: attachment; filename="' . $filename . '"');
        // Celles-ci pour IE
        header_wrapper("Expires: 0");
        header_wrapper("Cache-Control: must-revalidate, post-check=0,pre-check=0");
        header_wrapper("Pragma: public");

        if ($path) {
            if (! @readfile($path)) {
                $this->lastError = "Erreur lors de la lecture du fichier";
                return false;
            }
        }

        return true;
    }
}
