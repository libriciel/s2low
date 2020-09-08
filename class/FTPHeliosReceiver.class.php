<?php
class FTPHeliosReceiver implements Iterator {
    
	 /**
     * @var FTPService
     */
    private $FTPService;
    private $remotePath;
    private $localPath;
    private $filesToProcess = [];
    private $index = 0;

    public function __construct(FTPService $FTPService, $helios_ftp_response_server_path, $helios_ftp_response_tmp_local_path)
    {
        $this->FTPService = $FTPService;
        $this->remotePath = $helios_ftp_response_server_path;
        $this->localPath = $helios_ftp_response_tmp_local_path;
    }

    public function current(){
        $this->recupOneFile($this->filesToProcess[$this->index], $this->key());
        return $this->filesToProcess[$this->index];
    }

    public function key(){
        return $this->index;
    }

    public function next(){
        $this->index++;
    }

    public function valid()
    {
        $valid=isset($this->filesToProcess[$this->key()]);
        if(!$valid){
            $this->finTraitement();
        }
        return $valid;
    }

    public function rewind()
    {
        $this->index = 0;
    }

	private function isPesAller($filename){
        $isPesAller = preg_match("#^PESALR2_#",basename($filename));
        if($isPesAller){
            echo "$filename : PES ALLER ignoré\n";
        }
        return $isPesAller;
    }

    public function retrieveNames(){
        $this->FTPService->connect();

        echo "Remote_path : $this->remotePath\n";

        $all_file = $this->FTPService->getFileNames($this->remotePath);
        $this->filesToProcess = [];

        foreach ($all_file as $file){
            if(!$this->isPesAller(basename($file))){
                $this->filesToProcess[]=$file;
            }
        }
    }

    /**
     * @param $file
     * @param $i
     * @throws Exception
     */
    private function recupOneFile($file, $i): void
    {
        $ftp_get_result = $this->FTPService->retrieveFile($file, $this->localPath);
        echo $i . " : " . $file . " récupéré : " . ($ftp_get_result ? "SUCCES" : "ECHEC") . "\n";
    }

    public function finTraitement()
    {
        $this->FTPService->disconnect();
    }
}