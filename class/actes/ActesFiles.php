<?php

namespace S2lowLegacy\Class\actes;

class ActesFiles
{
    private $root_workspace_path;

    public function __construct($root_workspace_path)
    {
        $this->root_workspace_path = $root_workspace_path;
    }

    public function deleteFiles($envelope_path)
    {
        $dir_to_delete = dirname($this->root_workspace_path . "/"  . $envelope_path);
        escapeshellarg($dir_to_delete);
        if (! $dir_to_delete) {
            return false;
        }
        //FIXME : wtf ?
        // add path traversal check and use symfony filesystem instead of shell command
        shell_exec("rm -rf $dir_to_delete");
        return true;
    }
}
