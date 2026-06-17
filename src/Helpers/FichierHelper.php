<?php

namespace S2low\Helpers;

use S2lowLegacy\Class\Helpers;
use S2lowLegacy\Class\Trace;

class FichierHelper
{
    private const GENERATED_DIRS_PERMS = 0770;
    private const GENERATED_FILES_PERMS = 0660;

    private string $actesFilesUploadRoot;

    public function __construct(string $actes_files_upload_root = '')
    {
        $this->actesFilesUploadRoot = $actes_files_upload_root ?: (defined('ACTES_FILES_UPLOAD_ROOT') ? ACTES_FILES_UPLOAD_ROOT : '');
    }

    /**
     * @param string $path
     * @param string|null $base
     * @return bool
     */
    public function createDirTree($path, $base = null)
    {
        $base = $base ?? $this->actesFilesUploadRoot;
        $escBase = str_replace("/", '\/', $base);
        if (preg_match('/^' . $escBase . '\/*/', $path)) {
            $relPath = preg_replace('/^' . $escBase . '\\/*/', "", $path);
        } else {
            $t = Trace::getInstance();

            $t->log("Impossible de créer le répertoire (unknow reason): $path ", Trace::$TRACE_ERROR);
            return false;
        }

        if (! file_exists($path)) {
            if (!mkdir($path, self::GENERATED_DIRS_PERMS, true) && !is_dir($path)) {
                $t = Trace::getInstance();
                $t->log("Impossible de créer le répertoire (mkdir failed): $path ", Trace::$TRACE_ERROR);
                return false;
            }

            // Modification des permissions de toute l'arborescence créée
            while (mb_strlen($relPath) > 0) {
                $this->fixPerms($base . "/" . $relPath);
                $relPath = preg_replace('/[^\/]+\/*$/', "", $relPath);
            }
        } elseif (! is_dir($path)) {
            $t = Trace::getInstance();
            $t->log("Impossible de créer le répertoire (file exists): $path ", Trace::$TRACE_ERROR);
            return false;
        } else {
            return true;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function deleteFromFS(...$args)
    {
        $return_value = true;

        foreach ($args as $entry) {
            if (file_exists($entry)) {
                if (is_dir($entry)) {
                    if (! @rmdir($entry)) {
                        $return_value = false;
                    }
                } elseif (is_file($entry) || is_link($entry)) {
                    if (! @unlink($entry)) {
                        $return_value = false;
                    }
                }
            }
        }

        return $return_value;
    }

    /**
     * @param string $path
     * @return bool
     */
    public function fixPerms($path)
    {
        $t = Trace::getInstance();
        $t->log("Modification des droits de : $path ", Trace::$TRACE_DEBUG);

        if (file_exists($path)) {
            if (is_dir($path)) {
                $r =  chmod($path, self::GENERATED_DIRS_PERMS);
                if (! $r) {
                    $t->log("Echec de l'attribution des droits : $path ", Trace::$TRACE_ERROR);
                }
                return $r;
            } elseif (is_file($path)) {
                return chmod($path, self::GENERATED_FILES_PERMS);
            }
        }

        return false;
    }

    /**
     * @param string|null $path
     * @param string $filename
     * @param string|null $content_type
     * @return bool
     */
    public function sendFileToBrowser($path, $filename, $content_type = null)
    {
        if ($path) {
            if (! file_exists($path)) {
                Helpers::$last_error = "Fichier spécifié introuvable";
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
                Helpers::$last_error = "Erreur lors de la lecture du fichier";
                return false;
            }
        }

        return true;
    }



    /**
     * @param int $length
     * @param bool $prefix
     * @return string
     */
    public function genTempName($length = 8, $prefix = true)
    {
        if ($prefix) {
            $tmp = "__tmp__";
        } else {
            $tmp = "";
        }

        for ($i = 0; $i < $length; $i++) {
            $tmp .= rand(1, 9);
        }

        return $tmp;
    }

    /**
     * @param string $path
     * @return string|null
     */
    public function getFileType($path)
    {
        if (file_exists($path)) {
            return mime_content_type($path);
        } else {
            return null;
        }
    }
}
