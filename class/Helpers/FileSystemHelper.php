<?php

namespace S2lowLegacy\Class\Helpers;

use S2lowLegacy\Class\Trace;

class FileSystemHelper
{
    public const GENERATED_DIRS_PERMS = 0770;
    public const GENERATED_FILES_PERMS = 0660;

    public static function createDirTree($path, $base = ACTES_FILES_UPLOAD_ROOT)
    {

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

            while (mb_strlen($relPath) > 0) {
                self::fixPerms($base . "/" . $relPath);
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

    public static function deleteFromFS()
    {
        $return_value = true;

        for ($i = 0; $i < func_num_args(); $i++) {
            $entry = func_get_arg($i);
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

    public static function fixPerms($path)
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

    public static function getFileType($path)
    {
        if (file_exists($path)) {
            return mime_content_type($path);
        } else {
            return null;
        }
    }
}
