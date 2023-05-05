<?php

namespace S2low\Services\Helios\FTPConnection;

use UnexpectedValueException;

class SFTPServerProtocolConfiguration implements ServerProtocolConfiguration
{
    public function getFileNames(string $remote_path, ActiveConnection $activeConnection): array
    {
        return array_diff($activeConnection->nlist($remote_path), [".",".."]);
    }

    public function retrieveFile(string $tmp_file, $file, ActiveConnection $activeConnection): bool
    {
        try {
            $activeConnection->get($tmp_file, $file);
        } catch (\Exception $exception) {
            return false;
        }
        return true;
    }

    public function deleteIfNeedBe($file, ActiveConnection $activeConnection): void
    {
        // Aucun cas en SFTP nécessitant ça
    }

    public function close(ActiveConnection $activeConnection)
    {
        $activeConnection->close();
    }

    /**
     * @param string $p_dest
     * @param string $p_msg
     * @param $pAppli
     * @param $destinationDirectory
     * @param string $file_to_send
     * @param \S2low\Services\Helios\FTPConnection\ActiveConnection $activeConnection
     */
    public function sendOneFileWithProperties(
        string $p_dest,
        string $p_msg,
        $pAppli,
        $destinationDirectory,
        string $file_to_send,
        ActiveConnection $activeConnection
    ): void {
        $filename = basename($file_to_send);
        $passtransFileName = "$p_dest%$pAppli%$filename";
        $activeConnection->put("$destinationDirectory/$passtransFileName", $file_to_send);
    }

    public function getDemoModeAsString(): string
    {
        return "";  // Pas de mode démo pour le SFTP
    }
}
