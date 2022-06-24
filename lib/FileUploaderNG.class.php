<?php

class FileUploaderNG
{
    private $files;
    private $lastError;

    public function __construct()
    {
        $this->setFiles($_FILES);
    }

    public function setFiles($files)
    {
        $this->files = $files;
    }

    public function getFilePath($filename, $num_file = 0)
    {
        return $this->getValueIntern($filename, 'tmp_name', $num_file);
    }

    public function getName($filename, $num_file = 0)
    {
        return $this->getValueIntern($filename, 'name', $num_file);
    }

    public function getFileType($form_name, $num_file = 0)
    {
        $tmp_name = $this->getValueIntern($form_name, 'tmp_name', $num_file);
        if (! $tmp_name) {
            return false;
        }
        $finfo = new finfo();
        return $finfo->file($tmp_name, FILEINFO_MIME_TYPE);
    }

    public function getFileContent($form_name, $num_file = 0)
    {
        $tmp_name = $this->getValueIntern($form_name, 'tmp_name', $num_file);
        if (! $tmp_name) {
            return false;
        }
        return file_get_contents($tmp_name);
    }

    public function save($filename, $save_path, $num_file = 0)
    {
        move_uploaded_file_wrapper($this->getFilePath($filename, $num_file), $save_path);
    }

    public function getNbFile($form_name)
    {
        if (!isset($this->files[$form_name]['tmp_name'])) {
            $this->lastError = "Fichier $form_name inexistant";
            return false;
        }
        if (is_array($this->files[$form_name]['tmp_name'])) {
            return count($this->files[$form_name]['tmp_name']);
        } else {
            return 1;
        }
    }

    public function getAll()
    {
        $result = array();
        foreach ($this->files as $filename => $value) {
            $result[$filename] = $this->getName($filename);
        }
        return $result;
    }

    private function getValueIntern($name, $key, $num_file = 0)
    {
        if (! isset($this->files[$name][$key])) {
            $this->lastError = "Fichier $name inexistant";
            return false;
        }
        if (is_array($this->files[$name][$key])) {
            if (! isset($this->files[$name][$key][$num_file])) {
                $this->lastError = "Fichier $name:$num_file inexistant";
                return false;
            }
            return $this->files[$name][$key][$num_file];
        } else {
            return $this->files[$name][$key];
        }
    }

    public function getLastError()
    {
        switch ($this->lastError) {
            case UPLOAD_ERR_INI_SIZE:
                return "Le fichier dÃ©passe " . ini_get("upload_max_filesize");
            case UPLOAD_ERR_FORM_SIZE:
                return "Le fichier dÃ©passe la taille limite autorisÃ©e par le formulaire";
            case UPLOAD_ERR_PARTIAL:
                return "Le fichier n'a Ã©tÃ© que partiellement reÃ§u";
            case UPLOAD_ERR_NO_FILE:
                return "Aucun fichier n'a Ã©tÃ© reÃ§u";
            case UPLOAD_ERR_NO_TMP_DIR:
                return "Erreur de configuration : le rÃ©pertoire temporaire n'existe pas";
            case UPLOAD_ERR_CANT_WRITE:
                return "Erreur de configuration : Impossible d'Ã©crire dans le rÃ©pertoire temporaire";
            case UPLOAD_ERR_EXTENSION:
                return "Une extension PHP empeche l'upload du fichier!";
            default:
                return "Aucun fichier reÃ§u (code : {$this->lastError})";
        }
    }
}
