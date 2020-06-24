<?php
class FilesOnFtp implements Iterator {
    
	 /**
     * @var FTPConnection
     */
    private $FTPConnection;
    private $remotePath;
    private $localPath;
    private $filesToProcess = [];
    private $index = 0;

    public function __construct(FTPConnection $FTPConnection)
    {
        $this->FTPConnection = $FTPConnection;
        $this->remotePath = HELIOS_FTP_RESPONSE_SERVER_PATH;
        $this->localPath = HELIOS_FTP_RESPONSE_TMP_LOCAL_PATH;
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
        $this->FTPConnection->connect();

        $this->remote_path = "retrait";                                       //TODO : utiliser correctement la constante
        echo "Remote_path : $this->remote_path\n";

        $all_file = $this->FTPConnection->getFiles($this->remote_path);
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
        $ftp_get_result = $this->FTPConnection->retrieveFile($file, $this->localPath);
        echo $i . " : " . $file . " récupéré : " . ($ftp_get_result ? "SUCCES" : "ECHEC") . "\n";
    }

    public function finTraitement()
    {
        $this->FTPConnection->disconnect();
    }
}