<?php

namespace S2lowLegacy\Class;

class FileUploader
{
    private const MAX_LINE_LENGTH = 1024;

    private array $forbidenExtension = [
        'asp', 'aspx', 'asax', 'asa', 'jsp', 'cer', 'cdx', 'asa', 'htr', 'php', 'php3', 'exe', 'cgi'
    ];

    private string $destinationDirectory = '';
    private string $lastError = '';
    private string $fileName;
    private string $fileSize;
    private string $extension;

    private $fileHandler;


    public function setDestinationDirectory(string $directory): void
    {
        $this->destinationDirectory = $directory;
    }

    public function disableForbidenExtension(): void
    {
        $this->forbidenExtension = [];
    }


    public function verifOKAll(string $formFileName): bool
    {
        if (! isset($_FILES[$formFileName]) || ! $_FILES[$formFileName]) {
            $this->lastError = "Il n'y a pas de fichier à charger sur le serveur";
            return false;
        }

        foreach ($_FILES[$formFileName]['error'] as $i => $errorCode) {
            if ($errorCode !=  UPLOAD_ERR_OK) {
                $this->setErrorMessage($errorCode);
                return false;
            }

            if ($_FILES[$formFileName]['size'][$i] <= 0) {
                $this->lastError = "Le fichier {$_FILES[$formFileName]['name'][$i]} semble vide";
                return false;
            }
        }
        return true;
    }

    public function verifOK(string $formFileName): bool
    {
        if (! isset($_FILES[$formFileName]) || ! $_FILES[$formFileName]) {
            $this->lastError = "Il n'y a pas de fichier à charger sur le serveur";
            return false;
        }

        $errorCode = $_FILES[$formFileName]['error'];
        if ($errorCode !=  UPLOAD_ERR_OK) {
            $this->setErrorMessage($errorCode);
            return false;
        }

        $this->fileName = $_FILES[$formFileName]['name'];
        $this->fileSize = $_FILES[$formFileName]['size'];
        if ($this->fileSize <= 0) {
            $this->lastError = "Le fichier $this->fileName semble vide";
            return false;
        }
        return true;
    }

    public function upload(string $formFileName): bool
    {
        if (! $this->verifOK($formFileName)) {
            return false;
        }

        $this->extension = mb_strtolower(mb_substr($this->fileName, mb_strrpos($this->fileName, '.') + 1));

        if (in_array($this->extension, $this->forbidenExtension)) {
            $this->lastError = 'Le fichier ' . $this->fileName . ' contient une extension interdite';
            return false;
        }

        if (file_exists($this->destinationDirectory . $this->fileName)) {
            $this->lastError = 'Le fichier ' . $this->fileName . ' existe déjà sur le serveur';
            return false;
        }
        if ($this->destinationDirectory) {
            $res = move_uploaded_file(
                $_FILES[$formFileName]['tmp_name'],
                $this->destinationDirectory . $this->fileName
            );
            if (!$res) {
                $this->lastError = 'Impossible de recopier le fichier sur le serveur ';
                return false;
            }
        } else {
            $this->fileHandler = fopen($_FILES[$formFileName]['tmp_name'], 'r');
        }
        return true;
    }

    public function getLigne(): false|string
    {
        if (feof($this->fileHandler)) {
            return false;
        }
        return fgets($this->fileHandler, self::MAX_LINE_LENGTH);
    }

    private function setErrorMessage(int $code): void
    {
        $message = match ($code) {
            UPLOAD_ERR_INI_SIZE =>
                'La taille du fichier excède la taille maximum (' . ini_get('upload_max_filesize') . ')',
            UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la taille limite autorisée par le formulaire',
            UPLOAD_ERR_PARTIAL => "Le fichier n'a été que partiellement reçu",
            UPLOAD_ERR_NO_FILE => "Aucun fichier n'a été présenté",
            UPLOAD_ERR_NO_TMP_DIR => "Erreur de configuration : le répertoire temporaire n'existe pas",
            UPLOAD_ERR_CANT_WRITE => "Erreur de configuration : Impossible d'écrire dans le répertoire temporaire",
            UPLOAD_ERR_EXTENSION => "Une extension PHP empeche l'upload du fichier!",
            default => "Erreur lors du chargement du fichier (code $code)",
        };
        $this->lastError = $message;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getFileSize(): string
    {
        return $this->fileSize;
    }

    public function getLastError(): string
    {
        return $this->lastError;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }
}
