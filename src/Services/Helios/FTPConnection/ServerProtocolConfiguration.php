<?php

namespace S2low\Services\Helios\FTPConnection;

interface ServerProtocolConfiguration
{
    public function getFileNames(string $remote_path, ActiveConnection $activeConnection): array;

    public function retrieveFile(string $tmp_file, $file, ActiveConnection $activeConnection): bool;

    public function deleteIfNeedBe($file, ActiveConnection $activeConnection): void;

    public function sendOneFileWithProperties(
        string $p_dest,
        string $p_msg,
        $pAppli,
        $destinationDirectory,
        string $file_to_send,
        ActiveConnection $activeConnection
    ): void;

    public function getDemoModeAsString(): string;
}
