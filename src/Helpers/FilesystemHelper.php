<?php

namespace S2low\Helpers;

use S2lowLegacy\Class\Trace;

class FilesystemHelper
{
    private const GENERATED_DIRS_PERMS = 0770;
    private const GENERATED_FILES_PERMS = 0660;

    /**
     * @param string $path
     * @param string $base
     * @return bool
     */
    public function createDirTree(string $path, string $base = ACTES_FILES_UPLOAD_ROOT): bool
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
     * @param mixed ...$entries
     * @return bool
     */
    public function deleteFromFS(...$entries): bool
    {
        $return_value = true;

        foreach ($entries as $entry) {
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
    public function fixPerms(string $path): bool
    {
        $t = Trace::getInstance();
        $t->log("Modification des droits de : $path ", Trace::$TRACE_DEBUG);

        if (file_exists($path)) {
            if (is_dir($path)) {
                $r = chmod($path, self::GENERATED_DIRS_PERMS);
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
     * @param int $length
     * @param bool $prefix
     * @return string
     */
    public function genTempName(int $length = 8, bool $prefix = true): string
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
    public function getFileType(string $path): ?string
    {
        if (file_exists($path)) {
            return mime_content_type($path);
        } else {
            return null;
        }
    }
}
