<?php

namespace S2low\Services\Helios\FTPConnection;

interface ActiveConnection
{
    public function chdir(string $remote_path): bool;

    public function nlist(string $directory): array|bool;

    public function get(string $tmp_file, string $remoteFile): bool;

    public function raw($command): ?array;

    public function put(string $remoteFile, string $file_to_send): bool;

    public function delete($file): bool;

    public function close(): bool;
}
