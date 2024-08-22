<?php

namespace S2lowLegacy\Class;

use ActesBatch;

class BatchUploadHandler extends \S2lowLegacy\Class\UploadHandler
{
    public function __construct(
        private readonly ActesBatch $actesBatch,
        $options = null,
        $initialize = true,
        $error_messages = null,
    ) {
        parent::__construct($options, $initialize, $error_messages);
    }

    protected function initialize()
    {
        parent::initialize();
        if (!$this->actesBatch->save()) {
            $this->response['msg'] = [
                "msg" => "Erreur lors de l'enregistrement du lot " . $this->actesBatch->getId() . " " . $this->actesBatch->getErrorMsg(),
                "error_level" => 1
                ];
        }
        $this->response['msg'] = [
            "msg" => "Lot n&deg;" . $this->actesBatch->getId() . " cr&eacute;&eacute; avec succ&egrave;s.",
            "error_level" => 0
            ];
    }

    protected function handle_file_upload(
        $uploaded_file,
        $name,
        $size,
        $type,
        $error,
        $index = null,
        $content_range = null
    ) {
        $file = parent::handle_file_upload($uploaded_file, $name, $size, $type, $error, $index, $content_range);
        if (empty($file->error)) {
            $name = $file->name;
            if (!$this->actesBatch->addBatchFile(ACTES_BATCHES_UPLOAD_ROOT . "/$name", $name, "")) {
                $file->error = $this->actesBatch->getErrorMsg();
            }
        }
        return $file;
    }
}
